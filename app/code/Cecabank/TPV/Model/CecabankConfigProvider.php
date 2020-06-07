<?php

namespace Cecabank\TPV\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

use Cecabank\TPV\Model\CecabankModel;


class CecabankConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

    /**
     * Protected $model
     */
    protected $model;

    public function __construct(CecabankModel $model) {
        $this->model = $model;
    }

    public function getConfig()
    {
        return [
            'payment' => [
                'cecabank' => [
                    'description' => $this->model->getDescription(),
                    'image' => $this->model->getImage()
                ]
            ]
        ];
    }
}