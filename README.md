# Responsive Images

This extension provides a view helper for responsive images.

## Installation

```
composer require iresults/responsive-images
```

## Example usage

### Input

```html
<html
  xmlns:iresultsResponsiveImages="http://typo3.org/ns/Iresults/ResponsiveImages/ViewHelpers"
  data-namespace-typo3-fluid="true"
>
  <iresultsResponsiveImages:responsiveImage
    image="{project.imageFile}"
    widths="
             (max-width: 414px) 378px,
             (max-width: 575px) 540px,
             (max-width: 1399px) 546px,
             634px"
    pixelDensities="1,2"
  />
</html>
```

### Output

```html
<picture>
  <source
    srcset="image-path-378px.jpg, image-path-378px.jpg 2x"
    media="(max-width: 414px)"
  />
  <source
    srcset="image-path-540px.jpg, image-path-540px.jpg 2x"
    media="(max-width: 575px)"
  />
  <source
    srcset="image-path-546px.jpg, image-path-546px.jpg 2x"
    media="(max-width: 1399px)"
  />
  <source srcset="image-path-634px.jpg, image-path-634px.jpg 2x" media="" />
  <img src="image-path-634px.jpg" width="634" height="633" alt="" />
</picture>
```
