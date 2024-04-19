<?php

namespace NITSAN\NsT3Ai\Helper;

class NsExtensionConfiguration
{
    protected array $openAiPromptPrefixPageTitle = [
        'Professional' => 'Act as an SEO expert and write five an optimized title tag for a web page about [Content]',
        'Optimized' => 'Act as an SEO expert and write five an optimized title tag for a web page about [Content]; the title tag should not exceed 60 characters in length',
        'Catchy' => 'Act as an SEO expert and write five an optimized and catchy with user friendly tone title tag for a web page about [Content] the title tag should not exceed 60 characters in length',
    ];

    public function getPageTitlePrompts(): array
    {
        return $this->openAiPromptPrefixPageTitle;
    }
}