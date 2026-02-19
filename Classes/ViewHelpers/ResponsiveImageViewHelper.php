<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\ViewHelpers;

use InvalidArgumentException;
use Iresults\ResponsiveImages\Domain\Enum\SpecialFunction;
use Iresults\ResponsiveImages\Domain\ValueObject\CropInformation;
use Iresults\ResponsiveImages\Domain\ValueObject\SizeDefinition;
use Iresults\ResponsiveImages\Service\ImageResizingService;
use Iresults\ResponsiveImages\Service\SizesParser;
use RuntimeException;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
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
use function implode;
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
 *          image="{project.imageFile}"
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
 *          <source srcset="image-path-378px.jpg, image-path-378px.jpg 2x" media="(max-width: 414px)">
 *          <source srcset="image-path-540px.jpg, image-path-540px.jpg 2x" media="(max-width: 575px)">
 *          <source srcset="image-path-546px.jpg, image-path-546px.jpg 2x" media="(max-width: 1399px)">
 *          <source srcset="image-path-634px.jpg, image-path-634px.jpg 2x" media="">
 *          <img src="image-path-634px.jpg" width="634" height="633" alt="">
 *      </picture>
 *
 *
 * With additional arguments
 * -------------------------
 *
 * ::
 *
 *      <iresultsResponsiveImages:responsiveImage
 *          image="{project.imageFile}"
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
 *          <source srcset="image-path-378px.jpg, image-path-378px.jpg 2x" media="(max-width: 414px)">
 *          <source srcset="image-path-540px.jpg, image-path-540px.jpg 2x" media="(max-width: 575px)">
 *          <source srcset="image-path-546px.jpg, image-path-546px.jpg 2x" media="(max-width: 1399px)">
 *          <source srcset="image-path-634px.jpg, image-path-634px.jpg 2x" media="">
 *          <img src="image-path-634px.jpg" width="634" height="633" alt="">
 *      </picture>
 */
class ResponsiveImageViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'picture';

    private ImageResizingService $imageResizingService;

    private SizesParser $sizesParser;

    public function __construct()
    {
        parent::__construct();
        $this->imageResizingService = GeneralUtility::makeInstance(ImageResizingService::class);
        $this->sizesParser = GeneralUtility::makeInstance(SizesParser::class);
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
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
            'Custom file extension to use',
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
            '',
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
        $pictureTag = $this->tag;
        $imageTag = new TagBuilder('img');
        $image = $this->getImage($this->arguments);
        $this->validateFileExtensionArgument();
        $sizes = $this->sizesParser->parseSizes($this->arguments['widths']);
        $pixelDensities = $this->parsePixelDensities($this->arguments['pixelDensities']);
        $fileExtension = $this->arguments['fileExtension'] ?? '';
        $useAbsoluteUri = (bool) $this->arguments['absolute'];
        $specialFunction = $this->parseSpecialFunction();
        try {
            $cropInformation = $this->getCropInformation($image, $this->arguments);
            if (!$pictureTag->hasAttribute('data-focus-area')) {
                $focusArea = $cropInformation->variantCollection->getFocusArea($cropInformation->variant);
                if (!$focusArea->isEmpty()) {
                    $pictureTag->addAttribute('data-focus-area', $focusArea->makeAbsoluteBasedOnFile($image));
                }
            }

            $pictureTagContent = $this->renderSourceElements(
                $sizes,
                $pixelDensities,
                $image,
                $cropInformation->area,
                $fileExtension,
                $useAbsoluteUri,
                $imageTag,
                $specialFunction,
            );

            // Remove the `title` attribute from <picture> and add it to <img>
            $pictureTag->removeAttribute('title');
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
            // We have a domain model, so we need to fetch the FAL resource object from there
            $originalResource = $image->getOriginalResource();
            if (!($originalResource instanceof File || $originalResource instanceof FileReference)) {
                throw new UnexpectedValueException(
                    'No original resource could be resolved for supplied file ' . get_class($image),
                    1625838481,
                );
            }

            return $originalResource;
        }

        throw new UnexpectedValueException('Could not get image');
    }

    private function validateFileExtensionArgument(): void
    {
        if ((string) $this->arguments['fileExtension']
            && !GeneralUtility::inList(
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                (string) $this->arguments['fileExtension'],
            )
        ) {
            throw new Exception(
                'The extension ' . $this->arguments['fileExtension'] . ' is not specified in $GLOBALS[\'TYPO3_CONF_VARS\'][\'GFX\'][\'imagefile_ext\']'
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
        $cropString = $arguments['crop'];
        if (null === $cropString && $image->hasProperty('crop') && $image->getProperty('crop')) {
            $cropString = $image->getProperty('crop');
        }
        $variantCollection = CropVariantCollection::create((string) $cropString);
        $variant = $arguments['cropVariant'] ?: 'default';
        $cropArea = $variantCollection->getCropArea($variant);
        $area = $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image);

        return new CropInformation($variantCollection, $variant, $area);
    }

    /**
     * @return float[]
     */
    private function parsePixelDensities(string $pixelDensities): array
    {
        return array_map('floatval', array_filter(explode(',', $pixelDensities)));
    }

    /**
     * @param SizeDefinition[] $sizes
     * @param float[]          $pixelDensities
     */
    private function renderSourceElements(
        array $sizes,
        array $pixelDensities,
        File|FileReference $image,
        ?Area $crop,
        ?string $fileExtension,
        bool $useAbsoluteUri,
        TagBuilder $imageTag,
        ?SpecialFunction $specialFunction,
    ): string {
        $pictureTagContent = '';
        foreach ($sizes as $size) {
            $sourceTag = new TagBuilder('source');
            $srcsetOutput = [];
            foreach ($pixelDensities as $pixelDensity) {
                $resizedImage = $this->imageResizingService->resize(
                    $image,
                    $size,
                    $pixelDensity,
                    $crop,
                    $specialFunction,
                    $fileExtension,
                );

                $srcsetLine = $resizedImage->getPublicUrl($useAbsoluteUri);
                if (1 !== (int) $resizedImage->pixelDensity) {
                    $srcsetLine .= ' ' . $resizedImage->pixelDensity . 'x';
                }
                $srcsetOutput[] = $srcsetLine;
            }

            if ($size->isDefault) {
                $resizedFallbackImage = $this->imageResizingService->resize(
                    $image,
                    $size,
                    1.0,
                    $crop,
                    $specialFunction,
                    $fileExtension,
                );
                $imageTag->addAttribute('src', $resizedFallbackImage->getPublicUrl($useAbsoluteUri));
                $imageTag->addAttribute('width', $resizedFallbackImage->file->getProperty('width'));
                $imageTag->addAttribute('height', $resizedFallbackImage->file->getProperty('height'));
            }

            $sourceTag->addAttribute('srcset', implode(', ', $srcsetOutput));
            $sourceTag->addAttribute('media', $size->mediaCondition);

            $pictureTagContent .= $sourceTag->render();
        }

        return $pictureTagContent;
    }

    /**
     * @param array<string, string|null> $arguments
     */
    private function addAttributeIfArgumentIsSet(TagBuilder $imageTag, array $arguments, string $attributeName): void
    {
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
