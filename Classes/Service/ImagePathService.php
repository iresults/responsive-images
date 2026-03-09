<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Service;

use InvalidArgumentException;
use Iresults\ResponsiveImages\Domain\ValueObject\ProcessedImageInterface;
use Iresults\ResponsiveImages\Domain\ValueObject\ResizedImage;
use Iresults\ResponsiveImages\Domain\ValueObject\SymlinkedImage;
use Iresults\ResponsiveImages\Exception\FileSystemException;
use Iresults\ResponsiveImages\Result;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class ImagePathService
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
    ) {
        $this->assertValidPath($this->getAssetDirectory(), true);
    }

    /**
     * @param non-empty-string $fileNamePrefix
     *
     * @return Result<covariant ProcessedImageInterface, covariant FileSystemException>
     */
    public function createNamedFile(ResizedImage $image, string $fileNamePrefix): Result
    {
        $this->assertValidPath($fileNamePrefix, false);

        if (!$image->isStoredLocally()) {
            return new Result\Err(new FileSystemException(sprintf(
                'File %s is not stored locally',
                $image->getIdentifier(),
            )));
        }

        $destinationDirectoryUrl = ''
          . '/' . $this->getAssetDirectory()
          . '/' . $fileNamePrefix . '/';
        $destinationDirectoryPath = Environment::getPublicPath()
            . $destinationDirectoryUrl;

        if (!file_exists($destinationDirectoryPath)) {
            GeneralUtility::mkdir_deep($destinationDirectoryPath);
        }

        $fileName = $fileNamePrefix
            . '-' . $image->getConfigurationChecksum()
            . '-' . $image->sizeDefinition->imageWidth . $image->sizeDefinition->unit
            . '-' . $image->getPixelDensity() . 'x'
            . '.' . $image->getExtension();

        $destinationPath = $destinationDirectoryPath . $fileName;
        $destinationUrl = $destinationDirectoryUrl . $fileName;

        $source = $image->getPath();
        if (null === $source) {
            return new Result\Err(new FileSystemException(sprintf(
                'Could not get source path for file %s',
                $image->getIdentifier(),
            )));
        }

        // The link exists and points to an existing file
        if (file_exists($destinationPath)) {
            $realPath = realpath($destinationPath);
            if ($realPath && $realPath === $source) {
                // New destination matches the symlinks target -> do nothing
                return new Result\Ok(new SymlinkedImage(
                    resizedImage: $image,
                    publicUrl: $destinationUrl,
                ));
            }

            // Continue to remove the old link
        }

        if (is_link($destinationPath)) {
            // The link is broken or should be updated
            if (false === @unlink($destinationPath)) {
                return new Result\Err(new FileSystemException(
                    sprintf('Could not remove symlink "%s"', $destinationPath)
                ));
            }
        }

        if (false === @symlink($source, $destinationPath)) {
            return new Result\Err(new FileSystemException(sprintf(
                'Could not create symlink "%s" pointing to "%s"',
                $destinationPath,
                $source
            )));
        }

        return new Result\Ok(new SymlinkedImage(
            resizedImage: $image,
            publicUrl: $destinationUrl,
        ));
    }

    private function getAssetDirectory(): string
    {
        return trim(
            $this->extensionConfiguration->get('responsive_images', 'assetDirectory'),
            '/'
        );
    }

    private function assertValidPath(string $fileNamePrefix, bool $allowPathSegment): void
    {
        $allowedSpecialCharacters = ['_', '-'];
        if ($allowPathSegment) {
            $allowedSpecialCharacters[] = '/';
        }
        $clearedPrefix = str_replace(
            $allowedSpecialCharacters,
            '',
            $fileNamePrefix
        );
        if (!ctype_alnum($clearedPrefix)) {
            throw new InvalidArgumentException(
                sprintf(
                    'File name prefix may only contain alphanumeric characters and one of %s. "%s" given',
                    implode(', ', $allowedSpecialCharacters),
                    $fileNamePrefix,
                ),
                1772715135
            );
        }
    }
}
