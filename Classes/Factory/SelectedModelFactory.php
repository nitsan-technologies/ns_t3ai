<?php

namespace NITSAN\NsT3Ai\Factory;

class SelectedModelFactory
{
    public function checkSelectedModel($extConf): bool
    {
        return  $extConf['model'] === 'gpt-3.5-turbo' ||
                $extConf['model'] === 'gpt-3.5-turbo-16k' ||
                $extConf['model'] === 'gpt-4' ||
                $extConf['model'] === 'gpt-4-32k';
    }
}
