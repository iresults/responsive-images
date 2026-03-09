<?php

declare(strict_types=1);

namespace Iresults\ResponsiveImages\Domain\ValueObject;

interface ProcessedImageInterface
{
    /**
     * @see \TYPO3\CMS\Core\Resource\ProcessedFile::getIdentifier()
     *
     * @return non-empty-string
     */
    public function getIdentifier(): string;

    /**
     * @return non-empty-string
     */
    public function getPath(): ?string;

    /**
     * @return non-empty-string|null NULL if file is deleted, the generated URL otherwise
     */
    public function getPublicUrl(bool $absolute = false): ?string;

    public function getProcessedWidth(): int;

    public function getProcessedHeight(): int;

    public function getPixelDensity(): float;

    public function getConfigurationChecksum(): string;

    /**
     * @see \TYPO3\CMS\Core\Resource\ProcessedFile::getExtension()
     */
    public function getExtension(): string;

    /**
     * @see \TYPO3\CMS\Core\Resource\ProcessedFile::getMimeType()
     *
     * @return non-empty-string mime type
     */
    public function getMimeType(): string;
}
