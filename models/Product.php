<?php
class Product {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM products ORDER BY id DESC");
        return $stmt->fetchAll();
    }
    
    public function getFeatured() {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name,
                   CASE 
                       WHEN p.highlight_text IS NOT NULL THEN p.highlight_text
                       WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                       WHEN DATEDIFF(NOW(), p.created_at) <= 30 THEN 'New'
                       ELSE NULL 
                   END as display_badge
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT 6
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        // Decode JSON fields if present
        if ($product) {
            if (isset($product['benefits'])) {
                $product['benefits'] = json_decode($product['benefits'], true) ?? [];
            }
            if (isset($product['gallery_images'])) {
                $product['gallery_images'] = json_decode($product['gallery_images'], true) ?? [];
            }
        }
        return $product;
    }
    
    public function getByCategory($categoryId) {
        $stmt = $this->pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? ORDER BY p.id DESC");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (name, description, price, category_id, image_url, featured)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category_id'],
            $data['image_url'],
            $data['featured'] ?? 0
        ]);
    }
    
    public function update($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE products 
            SET name = ?, description = ?, price = ?, 
                category_id = ?, image_url = ?, featured = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category_id'],
            $data['image_url'],
            $data['featured'] ?? 0,
            $id
        ]);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function search($query) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM products 
            WHERE name LIKE ? OR description LIKE ?
            ORDER BY id DESC
        ");
        $searchTerm = "%{$query}%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
    
    public function getAllCategories() {
        // Select distinct names and the minimum ID associated with each unique name
        $stmt = $this->pdo->query("
            SELECT MIN(id) as id, name
            FROM categories
            GROUP BY name
            ORDER BY name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFiltered($conditions = [], $params = [], $sortBy = 'name_asc', $limit = 12, $offset = 0) {
        $fixedConditions = array_map(function($cond) {
            $cond = preg_replace('/\bname\b/', 'p.name', $cond);
            $cond = preg_replace('/\bdescription\b/', 'p.description', $cond);
            $cond = preg_replace('/\bprice\b/', 'p.price', $cond);
            $cond = preg_replace('/\bcategory_id\b/', 'p.category_id', $cond);
            return $cond;
        }, $conditions);
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
        if (!empty($fixedConditions)) {
            $sql .= " WHERE " . implode(" AND ", $fixedConditions);
        }
        // Sorting
        switch ($sortBy) {
            case 'price_asc':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY p.name DESC";
                break;
            case 'name_asc':
            default:
                $sql .= " ORDER BY p.name ASC";
                break;
        }
        $sql .= " LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $paramIndex = 1;
        foreach ($params as $value) {
            $stmt->bindValue($paramIndex++, $value);
        }
        $stmt->bindValue($paramIndex++, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as &$product) {
            if (isset($product['benefits'])) {
                $product['benefits'] = json_decode($product['benefits'], true) ?? [];
            }
            if (isset($product['gallery_images'])) {
                $product['gallery_images'] = json_decode($product['gallery_images'], true) ?? [];
            }
        }
        return $products;
    }

    public function getPriceRange() {
        $stmt = $this->pdo->query("
            SELECT MIN(price) as min_price, MAX(price) as max_price 
            FROM products
        ");
        return $stmt->fetch();
    }
    
    public function getProductsByIds($ids) {
        if (empty($ids)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $this->pdo->prepare("
            SELECT * FROM products 
            WHERE id IN ($placeholders)
            ORDER BY FIELD(id, $placeholders)
        ");
        
        // Double the IDs array since we need it twice in the query
        $params = array_merge($ids, $ids);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function searchWithFilters($query, $categoryId = null, $minPrice = null, $maxPrice = null) {
        $conditions = ["(name LIKE ? OR description LIKE ?)"];
        $params = ["%$query%", "%$query%"];
        if ($categoryId) {
            $conditions[] = "category_id = ?";
            $params[] = $categoryId;
        }
        if ($minPrice !== null) {
            $conditions[] = "price >= ?";
            $params[] = $minPrice;
        }
        if ($maxPrice !== null) {
            $conditions[] = "price <= ?";
            $params[] = $maxPrice;
        }
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(" AND ", $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? ORDER BY RAND() LIMIT ?
        ");
        $stmt->execute([$categoryId, $productId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get related products by category_id, excluding the current product.
     * @param int $categoryId
     * @param int $excludeId
     * @param int $limit
     * @return array
     */
    public function getRelated($categoryId, $excludeId, $limit = 4) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? ORDER BY RAND() LIMIT ?"
        );
        $stmt->bindValue(1, $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(2, $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode JSON fields for related products
        foreach ($products as &$product) {
            if (isset($product['benefits'])) {
                $product['benefits'] = json_decode($product['benefits'], true) ?? [];
            }
            if (isset($product['gallery_images'])) {
                $product['gallery_images'] = json_decode($product['gallery_images'], true) ?? [];
            }
        }
        return $products;
    }

    public function updateStock($id, $quantity) {
        $stmt = $this->pdo->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity + ? 
            WHERE id = ?
        ");
        return $stmt->execute([$quantity, $id]);
    }
    
    public function checkStock($id) {
        $stmt = $this->pdo->prepare("
            SELECT stock_quantity, backorder_allowed, low_stock_threshold 
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function isInStock($id, $requestedQuantity = 1) {
        $stock = $this->checkStock($id);
        if (!$stock) {
            return false;
        }
        
        return $stock['backorder_allowed'] || $stock['stock_quantity'] >= $requestedQuantity;
    }
    
    public function getLowStockProducts($threshold = null) {
        $sql = "
            SELECT * FROM products 
            WHERE stock_quantity <= COALESCE(?, low_stock_threshold)
            ORDER BY stock_quantity ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
    }
    
    public function updateStockSettings($id, $threshold, $backorderAllowed) {
        $stmt = $this->pdo->prepare("
            UPDATE products 
            SET low_stock_threshold = ?,
                backorder_allowed = ?
            WHERE id = ?
        ");
        return $stmt->execute([$threshold, $backorderAllowed, $id]);
    }

    public function getCount($conditions = [], $params = []) {
        // Ensure this prefixing matches getFiltered if ambiguous columns are possible
        $fixedConditions = array_map(function($cond) {
            $cond = preg_replace('/\\bname\\b/', 'p.name', $cond);
            $cond = preg_replace('/\\bdescription\\b/', 'p.description', $cond);
            $cond = preg_replace('/\\bprice\\b/', 'p.price', $cond);
            $cond = preg_replace('/\\bcategory_id\\b/', 'p.category_id', $cond);
            return $cond;
        }, $conditions);
        $needsCategoryJoin = false;
        foreach($fixedConditions as $cond) {
            if (strpos($cond, 'c.') !== false) {
                $needsCategoryJoin = true;
                break;
            }
        }
        $sql = "SELECT COUNT(p.id) as count FROM products p";
        if ($needsCategoryJoin) {
            $sql .= " LEFT JOIN categories c ON p.category_id = c.id";
        }
        if (!empty($fixedConditions)) {
            $sql .= " WHERE " . implode(" AND ", $fixedConditions);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? (int)$row['count'] : 0;
    }
}