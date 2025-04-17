# Media Files Documentation for The Scent

This document lists all media files required by the platform, their locations, and descriptions.

## 1. Hero Section Media

### Video Background
**Path:** `/public/videos/hero.mp4`
**Description:** A calming, ambient video showing:
- Essential oils being dropped into water
- Slow-motion ripples and swirls
- Soft, natural lighting
- Duration: 15-30 seconds, loops seamlessly
- Resolution: 1920x1080 minimum
- Format: MP4 (H.264)
- File size: Optimize for web (max 10MB)

## 2. Product Images

### Featured Products Collection
All product images should maintain consistent aspect ratio (1:1 recommended) and style.

#### Essential Oils
1. **Serenity Blend**
   - Path: `/public/images/products/serenity-blend.jpg`
   - Description: Glass bottle with lavender-based oil blend
   - Size: 800x800px

2. **Citrus Energy Oil**
   - Path: `/public/images/products/citrus-energy.jpg`
   - Description: Orange and lemon-based essential oil bottle
   - Size: 800x800px

3. **Lavender Dreams**
   - Path: `/public/images/products/lavender-dreams.jpg`
   - Description: Pure lavender essential oil bottle
   - Size: 800x800px

4. **Focus Formula**
   - Path: `/public/images/products/focus-formula.jpg`
   - Description: Rosemary and mint blend bottle
   - Size: 800x800px

#### Natural Soaps
1. **Lavender Calm Soap**
   - Path: `/public/images/products/lavender-soap.jpg`
   - Description: Purple-hued natural soap bar with dried lavender
   - Size: 800x800px

2. **Citrus Burst Soap**
   - Path: `/public/images/products/citrus-soap.jpg`
   - Description: Orange-colored soap with citrus peel texture
   - Size: 800x800px

3. **Forest Fresh Soap**
   - Path: `/public/images/products/forest-soap.jpg`
   - Description: Green-tinted soap with pine needle impressions
   - Size: 800x800px

4. **Ocean Minerals Soap**
   - Path: `/public/images/products/ocean-soap.jpg`
   - Description: Blue-swirled soap with sea salt crystals
   - Size: 800x800px

## 3. About Section Images

### Main About Image
**Path:** `/public/images/about.jpg`
**Description:** 
- Workspace or studio shot showing essential oil production
- Natural lighting, warm tones
- Size: 1200x800px
- Shows quality ingredients and craftsmanship

### Team/Process Images
**Path:** `/public/images/about/process-1.jpg` through `process-4.jpg`
**Description:**
- Series of 4 images showing production process
- Consistent style and lighting
- Size: 800x600px each

## 4. Background Elements

### Particles Background
**Path:** `/public/images/textures/particle.png`
**Description:**
- Small, white circular particle for particles.js
- Size: 32x32px
- PNG with transparency

### Section Backgrounds
1. **Quiz Section Background**
   - Path: `/public/images/backgrounds/quiz-bg.jpg`
   - Description: Subtle, blurred aromatherapy-themed background
   - Size: 1920x1080px
   - Low contrast for text overlay

2. **Newsletter Section Background**
   - Path: `/public/images/backgrounds/newsletter-bg.jpg`
   - Description: Light, minimal pattern
   - Size: 1920x600px

## 5. Icons and UI Elements

### Category Icons
**Path:** `/public/images/icons/`
- `essential-oils.svg` - Dropper icon
- `natural-soaps.svg` - Soap bar icon
- `gifts.svg` - Gift box icon
- `accessories.svg` - Accessories icon

### UI Icons
**Path:** `/public/images/ui/`
- `cart.svg` - Shopping cart icon
- `user.svg` - User account icon
- `search.svg` - Search magnifier icon
- `menu.svg` - Mobile menu hamburger

## 6. Logo Assets

### Main Logo
**Path:** `/public/images/logo/`
- `logo.svg` - Full color vector logo
- `logo-white.svg` - White version for dark backgrounds
- `favicon.ico` - Browser favicon
- `logo-192.png` - PWA icon (192x192px)
- `logo-512.png` - PWA icon (512x512px)

## Image Guidelines

1. **Format Requirements:**
   - Product images: JPG (85% quality)
   - Icons: SVG or PNG with transparency
   - Logos: SVG preferred, PNG fallback
   - Background textures: PNG with transparency

2. **Size Requirements:**
   - Product images: 800x800px minimum
   - Hero images: 1920x1080px minimum
   - Thumbnails: Generate from originals
   - Max file sizes:
     - JPG: 200KB
     - PNG: 100KB
     - SVG: 20KB

3. **Style Guidelines:**
   - Consistent lighting
   - Clean, minimalist backgrounds
   - Natural color palette
   - Professional product photography
   - High clarity and sharpness

## CDN Integration (Future)

For production deployment, consider serving these assets from a CDN:
```html
https://cdn.thescent.com/images/products/serenity-blend.jpg
```

## Placeholder Usage

Until final media assets are ready, you can use:
- placeholder.com for product images
- coverr.co for video content
- SVGRepo for icons

## Remaining Media Requirements

The following media files still need to be provided:

### 1. Hero Video (hero.mp4)
- Location: `/public/videos/hero.mp4`
- Requirements:
  - High-quality video footage showcasing essential oils or natural soap making
  - Recommended duration: 15-30 seconds
  - Format: MP4 with H.264 encoding
  - Resolution: 1920x1080 minimum
- Temporary Solution:
  - Use a placeholder video from Pexels or stock video services
  - Example: https://www.pexels.com/video/pouring-essential-oil-into-a-diffuser/

### 2. Product Images
- Location: `/public/images/products/*.jpg`
- Requirements:
  - High-resolution product photos (minimum 1200x1200px)
  - White or neutral background
  - Consistent lighting and style
  - JPG format with 80%+ quality
- Temporary Solution:
  - Use placeholder service like https://placehold.co/600x600?text=Essential+Oil
  - Or stock photos from Unsplash's wellness collection

### 3. About Section Images
- Location: `/public/images/about/*.jpg`
- Requirements:
  - Lifestyle photos showing product usage
  - Manufacturing/workshop images
  - Team photos
  - Resolution: 1600x900px recommended
- Temporary Solution:
  - Use relevant wellness/spa stock photos from Unsplash or Pexels

### 4. Background Images
- Location: `/public/images/backgrounds/`
- Needed for:
  - Quiz section backdrop
  - Newsletter section
  - Any parallax sections
- Requirements:
  - Subtle patterns or blurred natural scenes
  - Light enough for text overlay
  - Minimum 1920px wide
- Temporary Solution:
  - Use subtle patterns from https://www.toptal.com/designers/subtlepatterns/
  - Or nature backgrounds from Unsplash

### 5. Favicon
- Location: `/public/favicon.ico`
- Requirements:
  - Company logo in ICO format
  - Multiple sizes (16x16, 32x32, 48x48)
  - Also provide PNG version for modern browsers
- Temporary Solution:
  - Generate a simple placeholder using https://realfavicongenerator.net/
  - Use a simple "S" or drop icon until brand identity is finalized

## Placeholder Usage Instructions

Until final media assets are ready:

1. For videos:
```html
<video autoplay muted loop playsinline>
    <source src="https://example.com/placeholder-video.mp4" type="video/mp4">
    <!-- Fallback content -->
    <img src="/images/backgrounds/hero-fallback.jpg" alt="The Scent hero image">
</video>
```

2. For product images:
```html
<img src="https://placehold.co/600x600?text=Essential+Oil+1" 
     alt="Lavender Essential Oil"
     loading="lazy">
```

3. For background images in CSS:
```css
.quiz-section {
    background-image: url('https://subtlepatterns.com/patterns/natural-paper.png');
    background-repeat: repeat;
}
```

## Next Steps

1. Work with a professional photographer for product shots
2. Commission a videographer for hero video content
3. Schedule lifestyle photo session for about section
4. Design and create brand identity including favicon
5. Replace all placeholder content with final assets

## Note on Image Optimization

Once final images are ready:
- Compress all JPGs using tools like ImageOptim
- Convert to WebP format with JPG fallback
- Implement responsive images using srcset
- Enable lazy loading for better performance