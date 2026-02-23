<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\ViewHelpers;

use InvalidArgumentException;
use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
use Iresults\ResponsiveImages\Domain\ValueObject\CropInformation;
use Iresults\ResponsiveImages\Service\SizesParser;
use Iresults\ResponsiveImages\Service\SourceElementBuilder;
use RuntimeException;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;
use UnexpectedValueException;

use function array_filter;
use function array_map;
use function explode;
use function get_class;
use function is_callable;

/**
 * ViewHelper to output responsive images
 *
 * Examples
 * ========
 *
 * Simple
 * ------
 *
 * ::
 *
 *      <iresultsResponsiveImages:responsiveImage
 *          image="{jpegImageFile}"
 *          widths="
 *              (max-width: 414px) 378px,
 *              (max-width: 575px) 540px,
 *              (max-width: 1399px) 546px,
 *              634px"
 *          pixelDensities="1,2"
 *      />
 *
 * Output::
 *
 *      <picture>
 *          <source width="378" height="252" type="image/jpeg" srcset="image-path-378px.jpg, image-path-378px-2x.jpg 2x" media="(max-width: 414px)">
 *          <source width="540" height="360" type="image/jpeg" srcset="image-path-540px.jpg, image-path-540px-2x.jpg 2x" media="(max-width: 575px)">
 *          <source width="546" height="364" type="image/jpeg" srcset="image-path-546px.jpg, image-path-546px-2x.jpg 2x" media="(max-width: 1399px)">
 *          <source width="634" height="422" type="image/jpeg" srcset="image-path-634px.jpg, image-path-634px-2x.jpg 2x">
 *          <img src="image-path-634px.jpg" width="634" height="422" alt="">
 *      </picture>
 *
 *
 * With file extension "png"
 * -------------------------
 *
 * ::
 *
 *      <iresultsResponsiveImages:responsiveImage
 *          image="{jpegImageFile}"
 *          widths="
 *              (max-width: 414px) 378px,
 *              634px"
 *          pixelDensities="1,2"
 *          fileExtension="png"
 *          />
 *
 * Output::
 *
 *      <picture>
 *          <source width="378" height="252" type="image/png" srcset="image-path-378px.png, image-path-378px-2x.png 2x" media="(max-width: 414px)">
 *          <source width="634" height="422" type="image/png" srcset="image-path-634px.png, image-path-634px-2x.png 2x">
 *          <img src="image-path-634px.png" width="634" height="422" alt="">
 *      </picture>
 **
 *
 * With crop variant
 * -----------------
 *
 * ::
 *
 *      <iresultsResponsiveImages:responsiveImage
 *          image="{jpegImageFile}"
 *          widths="
 *              (max-width: 414px) 378px,
 *              (max-width: 575px) 540px,
 *              (max-width: 1399px) 546px,
 *              634px"
 *          pixelDensities="1,2"
 *          cropVariant="1:1"
 *          fileExtension="jpg"
 *          />
 *
 * Output::
 *
 *      <picture>
 *          <source width="378" height="378" type="image/jpeg" srcset="image-path-378px.jpg, image-path-378px-2x.jpg 2x" media="(max-width: 414px)">
 *          <source width="540" height="540" type="image/jpeg" srcset="image-path-540px.jpg, image-path-540px-2x.jpg 2x" media="(max-width: 575px)">
 *          <source width="546" height="546" type="image/jpeg" srcset="image-path-546px.jpg, image-path-546px-2x.jpg 2x" media="(max-width: 1399px)">
 *          <source width="634" height="634" type="image/jpeg" srcset="image-path-634px.jpg, image-path-634px-2x.jpg 2x">
 *          <img src="image-path-634px.jpg" width="634" height="634" alt="">
 *      </picture>
 *
 *
 * With `preferredFileExtension`
 * -----------------------------
 *
 * ::
 *
 *      <iresultsResponsiveImages:responsiveImage
 *          image="{jpegImageFile}"
 *          widths="
 *              (max-width: 414px) 378px,
 *              (max-width: 575px) 540px,
 *              (max-width: 1399px) 546px,
 *              634px"
 *          pixelDensities="1,2"
 *          preferredFileExtension="webp"
 *          />
 *
 * Output::
 *
 *      <picture>
 *          <source width="378" height="252" type="image/webp" srcset="image-path-378px.webp, image-path-378px-2x.webp 2x" media="(max-width: 414px)">
 *          <source width="378" height="252" type="image/jpeg" srcset="image-path-378px.jpg, image-path-378px-2x.jpg 2x" media="(max-width: 414px)">
 *          <source width="540" height="360" type="image/webp" srcset="image-path-540px.webp, image-path-540px-2x.webp 2x" media="(max-width: 575px)">
 *          <source width="540" height="360" type="image/jpeg" srcset="image-path-540px.jpg, image-path-540px-2x.jpg 2x" media="(max-width: 575px)">
 *          <source width="546" height="364" type="image/webp" srcset="image-path-546px.webp, image-path-546px-2x.webp 2x" media="(max-width: 1399px)">
 *          <source width="546" height="364" type="image/jpeg" srcset="image-path-546px.jpg, image-path-546px-2x.jpg 2x" media="(max-width: 1399px)">
 *          <source width="634" height="422" type="image/webp" srcset="image-path-634px.webp, image-path-634px-2x.webp 2x">
 *          <source width="634" height="422" type="image/jpeg" srcset="image-path-634px.jpg, image-path-634px-2x.jpg 2x">
 *          <img src="image-path-634px.jpg" width="634" height="422" alt="">
 *      </picture>
 *
 *
 * Without media-queries and with `preferredFileExtension`
 * -------------------------
 *
 * ::
 *
 *      <iresultsResponsiveImages:responsiveImage
 *          image="{jpegImageFile}"
 *          widths="634px"
 *          pixelDensities="1,2"
 *          preferredFileExtension="webp"
 *          fileExtension="jpg"
 *          />
 *
 * Output::
 *
 *      <picture>
 *          <source width="378" height="378" type="image/webp" srcset="image-path-378px.webp, image-path-378px-2x.webp 2x" media="(max-width: 414px)">
 *          <source width="378" height="378" type="image/jpeg" srcset="image-path-378px.jpg, image-path-378px-2x.jpg 2x" media="(max-width: 414px)">
 *          <source width="540" height="540" type="image/webp" srcset="image-path-540px.webp, image-path-540px-2x.webp 2x" media="(max-width: 575px)">
 *          <source width="540" height="540" type="image/jpeg" srcset="image-path-540px.jpg, image-path-540px-2x.jpg 2x" media="(max-width: 575px)">
 *          <source width="546" height="546" type="image/webp" srcset="image-path-546px.webp, image-path-546px-2x.webp 2x" media="(max-width: 1399px)">
 *          <source width="546" height="546" type="image/jpeg" srcset="image-path-546px.jpg, image-path-546px-2x.jpg 2x" media="(max-width: 1399px)">
 *          <source width="634" height="634" type="image/webp" srcset="image-path-634px.webp, image-path-634px-2x.webp 2x">
 *          <source width="634" height="634" type="image/jpeg" srcset="image-path-634px.jpg, image-path-634px-2x.jpg 2x">
 *          <img src="image-path-634px.jpg" width="634" height="634" alt="">
 *      </picture>
 */
class ResponsiveImageViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'picture';

    private readonly SizesParser $sizesParser;

    private readonly SourceElementBuilder $sourceElementBuilder;

    public function __construct()
    {
        parent::__construct();
        $this->sizesParser = GeneralUtility::makeInstance(SizesParser::class);
        $this->sourceElementBuilder = GeneralUtility::makeInstance(SourceElementBuilder::class);
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'alt',
            'string',
            'Specifies an alternate text for an image',
            false,
        );
        $this->registerArgument(
            'ismap',
            'string',
            'Specifies an image as a server-side image-map. Rarely used. Look at usemap instead',
            false,
        );

        $this->registerArgument(
            'usemap',
            'string',
            'Specifies an image as a client-side image-map',
            false,
        );
        $this->registerArgument(
            'loading',
            'string',
            'Native lazy-loading for images property. Can be "lazy", "eager" or "auto"',
            false,
        );
        $this->registerArgument(
            'decoding',
            'string',
            'Provides an image decoding hint to the browser. Can be "sync", "async" or "auto"',
            false,
        );

        $this->registerArgument(
            'image',
            'object',
            'FAL object (' . File::class . ' or ' . FileReference::class . ')',
            true,
        );

        $this->registerArgument(
            'crop',
            'string|bool',
            'overrule cropping of image (setting to FALSE disables the cropping set in FileReference)',
        );
        $this->registerArgument(
            'cropVariant',
            'string',
            'select a cropping variant, in case multiple croppings have been specified or stored in FileReference',
            false,
            'default',
        );
        $this->registerArgument(
            'fileExtension',
            'string',
            'File extension to use to generate main files',
        );

        $this->registerArgument(
            'preferredFileExtension',
            'string',
            'Generate additional <source> entries using this file extension. These <source> entries will be add before the default entries',
        );

        $this->registerArgument(
            'widths',
            'string',
            'Definition of media conditions and width (https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#sizes)',
            false,
            '',
        );
        $this->registerArgument(
            'pixelDensities',
            'string',
            'List of additional pixel densities to render (https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#srcset)',
            false,
            '1',
        );
        $this->registerArgument(
            'absolute',
            'bool',
            'Force absolute URL',
            false,
            false,
        );
        $this->registerArgument(
            'specialFunction',
            'string',
            'Special function to apply when manipulating the images (e.g. "square")',
        );
    }

    public function render(): string
    {
        $fileExtension = $this->arguments['fileExtension'] ?? '';
        $preferredFileExtension = $this->arguments['preferredFileExtension'] ?? '';
        $this->validateFileExtensionArgument($fileExtension);
        $this->validateFileExtensionArgument($preferredFileExtension);

        $pictureTag = $this->tag;
        $image = $this->getImage($this->arguments);
        $sizes = $this->sizesParser->parseSizes($this->arguments['widths']);
        $pixelDensities = $this->parsePixelDensities($this->arguments['pixelDensities']);
        $useAbsoluteUri = (bool) $this->arguments['absolute'];
        $specialFunction = $this->parseSpecialFunction();
        try {
            // @phpstan-ignore argument.type
            $cropInformation = $this->getCropInformation($image, $this->arguments);
            if (!$pictureTag->hasAttribute('data-focus-area')) {
                $focusArea = $cropInformation->variantCollection
                    ->getFocusArea($cropInformation->variant);
                if (!$focusArea->isEmpty()) {
                    $pictureTag->addAttribute(
                        'data-focus-area',
                        (string) $focusArea->makeAbsoluteBasedOnFile($image)
                    );
                }
            }

            $imageTagOption = $this->sourceElementBuilder->buildImgTag(
                $sizes,
                $image,
                $cropInformation->area,
                $fileExtension,
                $useAbsoluteUri,
                $specialFunction,
                $this->additionalArguments
            );

            $pictureTagContent = $this->sourceElementBuilder->renderSourceElements(
                $sizes,
                $pixelDensities,
                $image,
                $cropInformation->area,
                $fileExtension,
                $preferredFileExtension,
                $useAbsoluteUri,
                $specialFunction,
            );

            // Remove the `title` attribute from <picture> and add it to <img>
            $pictureTag->removeAttribute('title');

            if ($imageTagOption->isSome()) {
                $imageTag = $imageTagOption->unwrap();
                $title = $this->arguments['title']
                    ?? (string) ($image->hasProperty('title') ? $image->getProperty('title') : '');
                if ('' !== $title) {
                    $imageTag->addAttribute('title', $title);
                }
                $altAttribute = $this->arguments['alt']
                    ?? ($image->hasProperty('alternative') ? $image->getProperty('alternative') : '');
                $imageTag->addAttribute('alt', $altAttribute);

                $this->addAttributeIfArgumentIsSet($imageTag, $this->arguments, 'ismap');
                $this->addAttributeIfArgumentIsSet($imageTag, $this->arguments, 'usemap');
                $this->addAttributeIfArgumentIsSet($imageTag, $this->arguments, 'loading');
                $this->addAttributeIfArgumentIsSet($imageTag, $this->arguments, 'decoding');

                $pictureTagContent .= $imageTag->render();
            }

            $this->tag->setContent($pictureTagContent);
        } catch (ResourceDoesNotExistException $e) {
            // thrown if file does not exist
            throw new Exception($e->getMessage(), 1509741911, $e);
        } catch (UnexpectedValueException $e) {
            // thrown if a file has been replaced with a folder
            throw new Exception($e->getMessage(), 1509741912, $e);
        } catch (RuntimeException $e) {
            // RuntimeException thrown if a file is outside a storage
            throw new Exception($e->getMessage(), 1509741913, $e);
        } catch (InvalidArgumentException $e) {
            // thrown if file storage does not exist
            throw new Exception($e->getMessage(), 1509741914, $e);
        }

        return $this->tag->render();
    }

    /**
     * @param array{image?:File|FileReference|object} $arguments
     */
    private function getImage(array $arguments): File|FileReference
    {
        if (empty($arguments['image'])) {
            throw new InvalidArgumentException('Missing image');
        }
        $image = $arguments['image'];
        if ($image instanceof File || $image instanceof FileReference) {
            // We already received a valid file and therefore just return it
            return $image;
        }

        if (is_callable([$image, 'getOriginalResource'])) {
            // We have a domain model, so we need to fetch the FAL resource
            // object from there
            $originalResource = $image->getOriginalResource();
            $validFileType = $originalResource instanceof File
                || $originalResource instanceof FileReference;
            if (!($validFileType)) {
                throw new UnexpectedValueException(
                    'No original resource could be resolved for supplied file ' . get_class($image),
                    1625838481,
                );
            }

            return $originalResource;
        }

        throw new UnexpectedValueException('Could not get image');
    }

    private function validateFileExtensionArgument(string $fileExtension): void
    {
        if (empty($fileExtension)) {
            return;
        }

        $allowedImageFileExtensions = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
        if (!GeneralUtility::inList($allowedImageFileExtensions, $fileExtension)) {
            throw new Exception(
                'The extension ' . $fileExtension . ' is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\']'
                . ' as a valid image file extension and can not be processed.',
                1618989190,
            );
        }
    }

    /**
     * @param array{crop?:string|null, cropVariant:?string} $arguments
     */
    private function getCropInformation(File|FileReference $image, array $arguments): CropInformation
    {
        $cropString = $arguments['crop'] ?? null;
        if (null === $cropString && $image->hasProperty('crop') && $image->getProperty('crop')) {
            $cropString = $image->getProperty('crop');
        }
        $variantCollection = CropVariantCollection::create((string) $cropString);
        $variant = $arguments['cropVariant'] ?: 'default';
        $cropArea = $variantCollection->getCropArea($variant);
        $area = $cropArea->isEmpty()
            ? null
            : $cropArea->makeAbsoluteBasedOnFile($image);

        return new CropInformation($variantCollection, $variant, $area);
    }

    /**
     * @return non-empty-array<float>
     */
    private function parsePixelDensities(string $rawPixelDensities): array
    {
        $pixelDensities = array_map('floatval', array_filter(explode(',', $rawPixelDensities)));
        if (empty($pixelDensities)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Could not parse the pixel densities "%s"',
                    $rawPixelDensities
                ),
                1771506987,
            );
        }

        return $pixelDensities;
    }

    /**
     * @param array<string, string|null> $arguments
     */
    private function addAttributeIfArgumentIsSet(
        TagBuilder $imageTag,
        array $arguments,
        string $attributeName,
    ): void {
        if (isset($arguments[$attributeName]) && '' !== $arguments[$attributeName]) {
            $imageTag->addAttribute($attributeName, $arguments[$attributeName]);
        }
    }

    private function parseSpecialFunction(): ?SpecialFunction
    {
        return isset($this->arguments['specialFunction'])
            ? SpecialFunction::from($this->arguments['specialFunction'])
            : null;
    }
}
