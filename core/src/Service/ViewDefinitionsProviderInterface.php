<?php

namespace App\Service;

use App\Entity\ViewDefinition;
use Exception;

interface ViewDefinitionsProviderInterface
{

    /**
     * Get list-view defs
     * @param string $moduleName
     * @return ViewDefinition
     * @throws Exception
     */
    public function getListViewDef(string $moduleName): ViewDefinition;

    /**
     * Get view definitions
     * @param string $moduleName
     * @param array $views
     * @return ViewDefinition
     * @throws Exception
     */
    public function getViewDefs(string $moduleName, array $views = []): ViewDefinition;

    /**
     * Get search defs
     * @param string $moduleName
     * @return ViewDefinition
     * @throws Exception
     */
    public function getSearchDefs(string $moduleName): ViewDefinition;
}
