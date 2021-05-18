<?php

declare (strict_types=1);
namespace Rector\FileFormatter\Formatter;

use RectorPrefix20210518\Nette\Utils\Strings;
use Rector\Core\ValueObject\Application\File;
use Rector\FileFormatter\Contract\Formatter\FileFormatterInterface;
use Rector\FileFormatter\ValueObject\EditorConfigConfiguration;
use Rector\FileFormatter\ValueObject\Indent;
use Rector\FileFormatter\ValueObjectFactory\EditorConfigConfigurationBuilder;
/**
 * @see \Rector\Tests\FileFormatter\Formatter\XmlFileFormatter\XmlFileFormatterTest
 */
final class XmlFileFormatter implements \Rector\FileFormatter\Contract\Formatter\FileFormatterInterface
{
    /**
     * @var string
     */
    private const XML_PARTS = '#(>)(<)(\\/*)#';
    /**
     * @var string
     */
    private const IS_OPENING_TAG = '#^<[^\\/]*>$#';
    /**
     * @var string
     */
    private const IS_CLOSING_TAG = '#^\\s*<\\/#';
    /**
     * @var int|null
     */
    private $depth;
    /**
     * @var int
     */
    private $indent = 4;
    /**
     * @var string
     */
    private $padChar = ' ';
    /**
     * @var bool
     */
    private $preserveWhitespace = \false;
    public function supports(\Rector\Core\ValueObject\Application\File $file) : bool
    {
        $smartFileInfo = $file->getSmartFileInfo();
        return $smartFileInfo->getExtension() === 'xml';
    }
    public function format(\Rector\Core\ValueObject\Application\File $file, \Rector\FileFormatter\ValueObject\EditorConfigConfiguration $editorConfigConfiguration) : void
    {
        $this->padChar = $editorConfigConfiguration->getIndentStyleCharacter();
        $this->indent = $editorConfigConfiguration->getIndentSize();
        $newFileContent = $this->formatXml($file->getFileContent());
        $newFileContent .= $editorConfigConfiguration->getFinalNewline();
        $file->changeFileContent($newFileContent);
    }
    public function createDefaultEditorConfigConfigurationBuilder() : \Rector\FileFormatter\ValueObjectFactory\EditorConfigConfigurationBuilder
    {
        $editorConfigConfigurationBuilder = new \Rector\FileFormatter\ValueObjectFactory\EditorConfigConfigurationBuilder();
        $editorConfigConfigurationBuilder->withIndent(\Rector\FileFormatter\ValueObject\Indent::createTabWithSize(1));
        return $editorConfigConfigurationBuilder;
    }
    private function formatXml(string $xml) : string
    {
        $output = '';
        $this->depth = 0;
        $parts = $this->getXmlParts($xml);
        if (\strpos($parts[0], '<?xml') === 0) {
            $output = \array_shift($parts) . \PHP_EOL;
        }
        foreach ($parts as $part) {
            $output .= $this->getOutputForPart($part);
        }
        return \trim($output);
    }
    /**
     * @return string[]
     */
    private function getXmlParts(string $xml) : array
    {
        $withNewLines = \RectorPrefix20210518\Nette\Utils\Strings::replace(\trim($xml), self::XML_PARTS, "\$1\n\$2\$3");
        return \explode("\n", $withNewLines);
    }
    private function getOutputForPart(string $part) : string
    {
        $output = '';
        $this->runPre($part);
        if ($this->preserveWhitespace) {
            $output .= $part . \PHP_EOL;
        } else {
            $part = \trim($part);
            $output .= $this->getPaddedString($part) . \PHP_EOL;
        }
        $this->runPost($part);
        return $output;
    }
    private function runPre(string $part) : void
    {
        if ($this->isClosingTag($part)) {
            --$this->depth;
        }
    }
    private function runPost(string $part) : void
    {
        if ($this->isOpeningTag($part)) {
            ++$this->depth;
        }
        if ($this->isClosingCdataTag($part)) {
            $this->preserveWhitespace = \false;
        }
        if ($this->isOpeningCdataTag($part)) {
            $this->preserveWhitespace = \true;
        }
    }
    private function getPaddedString(string $part) : string
    {
        return \str_pad($part, \strlen($part) + $this->depth * $this->indent, $this->padChar, \STR_PAD_LEFT);
    }
    private function isOpeningTag(string $part) : bool
    {
        return (bool) \RectorPrefix20210518\Nette\Utils\Strings::match($part, self::IS_OPENING_TAG);
    }
    private function isClosingTag(string $part) : bool
    {
        return (bool) \RectorPrefix20210518\Nette\Utils\Strings::match($part, self::IS_CLOSING_TAG);
    }
    private function isOpeningCdataTag(string $part) : bool
    {
        return \RectorPrefix20210518\Nette\Utils\Strings::contains($part, '<![CDATA[');
    }
    private function isClosingCdataTag(string $part) : bool
    {
        return \RectorPrefix20210518\Nette\Utils\Strings::contains($part, ']]>');
    }
}
