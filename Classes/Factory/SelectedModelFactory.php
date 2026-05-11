<?php

namespace NITSAN\NsT3Ai\Factory;

class SelectedModelFactory
{
    public function checkSelectedModel($extConf): bool
    {
        return  
                $extConf['model'] === 'gpt-4' ||
                $extConf['model'] === 'gpt-4o';
    }
}
