<?php

namespace Blog\Controller;

use Zend\View\Model\ViewModel,
	Zend\Mvc\Controller\ActionController;

class BlogController extends ActionController
{
    public function blogAction()
    {
        return array(
            'modules' => $this->getLocator()->get('ZFMLL\Module\Manager')->getModules()
        );
    }
}