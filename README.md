# Responsive Images

This extension provides a view helper for responsive images.

## Installation

```
composer require iresults/responsive-images
```

## Examples

### Simple

```html
<iresultsResponsiveImages:responsiveImage
    image="{jpegImageFile}"
    widths="
              (max-width: 414px) 378px,
              (max-width: 575px) 540px,
              (max-width: 1399px) 546px,
              634px"
    pixelDensities="1,2"
/>
```

```html
<picture>
    <source
        width="378"
        height="252"
        type="image/jpeg"
        srcset="image-path-378px.jpg, image-path-378px-2x.jpg 2x"
        media="(max-width: 414px)"
    />
    <source
        width="540"
        height="360"
        type="image/jpeg"
        srcset="image-path-540px.jpg, image-path-540px-2x.jpg 2x"
        media="(max-width: 575px)"
    />
    <source
        width="546"
        height="364"
        type="image/jpeg"
        srcset="image-path-546px.jpg, image-path-546px-2x.jpg 2x"
        media="(max-width: 1399px)"
    />
    <source
        width="634"
        height="422"
        type="image/jpeg"
        srcset="image-path-634px.jpg, image-path-634px-2x.jpg 2x"
    />
    <img src="image-path-634px.jpg" width="634" height="422" alt="" />
</picture>
```

### With file extension "png"

```html
<iresultsResponsiveImages:responsiveImage
    image="{jpegImageFile}"
    widths="
              (max-width: 414px) 378px,
              634px"
    pixelDensities="1,2"
    fileExtension="png"
/>
```

```html
<picture>
    <source
        width="378"
        height="252"
        type="image/png"
        srcset="image-path-378px.png, image-path-378px-2x.png 2x"
        media="(max-width: 414px)"
    />
    <source
        width="634"
        height="422"
        type="image/png"
        srcset="image-path-634px.png, image-path-634px-2x.png 2x"
    />
    <img src="image-path-634px.png" width="634" height="422" alt="" />
</picture>
```

### With crop variant

```html
<iresultsResponsiveImages:responsiveImage
    image="{jpegImageFile}"
    widths="
              (max-width: 414px) 378px,
              (max-width: 575px) 540px,
              (max-width: 1399px) 546px,
              634px"
    pixelDensities="1,2"
    cropVariant="1:1"
    fileExtension="jpg"
/>
```

```html
<picture>
    <source
        width="378"
        height="378"
        type="image/jpeg"
        srcset="image-path-378px.jpg, image-path-378px-2x.jpg 2x"
        media="(max-width: 414px)"
    />
    <source
        width="540"
        height="540"
        type="image/jpeg"
        srcset="image-path-540px.jpg, image-path-540px-2x.jpg 2x"
        media="(max-width: 575px)"
    />
    <source
        width="546"
        height="546"
        type="image/jpeg"
        srcset="image-path-546px.jpg, image-path-546px-2x.jpg 2x"
        media="(max-width: 1399px)"
    />
    <source
        width="634"
        height="634"
        type="image/jpeg"
        srcset="image-path-634px.jpg, image-path-634px-2x.jpg 2x"
    />
    <img src="image-path-634px.jpg" width="634" height="634" alt="" />
</picture>
```

### With `preferredFileExtension`

```html
<iresultsResponsiveImages:responsiveImage
    image="{jpegImageFile}"
    widths="
              (max-width: 414px) 378px,
              (max-width: 575px) 540px,
              (max-width: 1399px) 546px,
              634px"
    pixelDensities="1,2"
    preferredFileExtension="webp"
/>
```

```html
<picture>
    <source
        width="378"
        height="252"
        type="image/webp"
        srcset="image-path-378px.webp, image-path-378px-2x.webp 2x"
        media="(max-width: 414px)"
    />
    <source
        width="378"
        height="252"
        type="image/jpeg"
        srcset="image-path-378px.jpg, image-path-378px-2x.jpg 2x"
        media="(max-width: 414px)"
    />
    <source
        width="540"
        height="360"
        type="image/webp"
        srcset="image-path-540px.webp, image-path-540px-2x.webp 2x"
        media="(max-width: 575px)"
    />
    <source
        width="540"
        height="360"
        type="image/jpeg"
        srcset="image-path-540px.jpg, image-path-540px-2x.jpg 2x"
        media="(max-width: 575px)"
    />
    <source
        width="546"
        height="364"
        type="image/webp"
        srcset="image-path-546px.webp, image-path-546px-2x.webp 2x"
        media="(max-width: 1399px)"
    />
    <source
        width="546"
        height="364"
        type="image/jpeg"
        srcset="image-path-546px.jpg, image-path-546px-2x.jpg 2x"
        media="(max-width: 1399px)"
    />
    <source
        width="634"
        height="422"
        type="image/webp"
        srcset="image-path-634px.webp, image-path-634px-2x.webp 2x"
    />
    <source
        width="634"
        height="422"
        type="image/jpeg"
        srcset="image-path-634px.jpg, image-path-634px-2x.jpg 2x"
    />
    <img src="image-path-634px.jpg" width="634" height="422" alt="" />
</picture>
```

### Without media-queries and with `preferredFileExtension`

```html
<iresultsResponsiveImages:responsiveImage
    image="{jpegImageFile}"
    widths="634px"
    pixelDensities="1,2"
    preferredFileExtension="webp"
    fileExtension="jpg"
/>
```

```html
<picture>
    <source
        width="378"
        height="378"
        type="image/webp"
        srcset="image-path-378px.webp, image-path-378px-2x.webp 2x"
        media="(max-width: 414px)"
    />
    <source
        width="378"
        height="378"
        type="image/jpeg"
        srcset="image-path-378px.jpg, image-path-378px-2x.jpg 2x"
        media="(max-width: 414px)"
    />
    <source
        width="540"
        height="540"
        type="image/webp"
        srcset="image-path-540px.webp, image-path-540px-2x.webp 2x"
        media="(max-width: 575px)"
    />
    <source
        width="540"
        height="540"
        type="image/jpeg"
        srcset="image-path-540px.jpg, image-path-540px-2x.jpg 2x"
        media="(max-width: 575px)"
    />
    <source
        width="546"
        height="546"
        type="image/webp"
        srcset="image-path-546px.webp, image-path-546px-2x.webp 2x"
        media="(max-width: 1399px)"
    />
    <source
        width="546"
        height="546"
        type="image/jpeg"
        srcset="image-path-546px.jpg, image-path-546px-2x.jpg 2x"
        media="(max-width: 1399px)"
    />
    <source
        width="634"
        height="634"
        type="image/webp"
        srcset="image-path-634px.webp, image-path-634px-2x.webp 2x"
    />
    <source
        width="634"
        height="634"
        type="image/jpeg"
        srcset="image-path-634px.jpg, image-path-634px-2x.jpg 2x"
    />
    <img src="image-path-634px.jpg" width="634" height="634" alt="" />
</picture>
```
