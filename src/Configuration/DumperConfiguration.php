<?php

namespace Digilist\SnakeDumper\Configuration;

use Digilist\SnakeDumper\Configuration\Table\DataDependentFilterConfiguration;
use Digilist\SnakeDumper\Configuration\Table\TableConfiguration;

class DumperConfiguration extends AbstractConfiguration implements DumperConfigurationInterface
{

    /**
     * @var DatabaseConfiguration
     */
    private $databaseConfiguration;

    /**
     * @var OutputConfiguration
     */
    private $outputConfiguration;

    /**
     * @var TableConfiguration[]
     */
    private $tableConfigurations = array();

    /**
     * @return DatabaseConfiguration
     */
    public function getDatabase()
    {
        return $this->databaseConfiguration;
    }

    /**
     * @return TableConfiguration[]
     */
    public function getTables()
    {
        return $this->tableConfigurations;
    }

    /**
     * @return array
     */
    public function getTableWhiteList()
    {
        return $this->get('table_white_list', array());
    }

    /**
     * @param array $list
     * @return $this
     */
    public function setTableWhiteList(array $list)
    {
        return $this->set('table_white_list', $list);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasTable($name)
    {
        return array_key_exists($name, $this->tableConfigurations);
    }

    /**
     * Returns the table for configuration of the table with the passed name.
     * If there is no configuration, null will be returned.
     *
     * @param string $name
     *
     * @return TableConfiguration
     */
    public function getTable($name)
    {
        if (!array_key_exists($name, $this->tableConfigurations)) {
            return null;
        }

        return $this->tableConfigurations[$name];
    }

    /**
     * Returns the table for configuration of the table with the passed name.
     * If there is no configuration, null will be returned.
     *
     * @param TableConfiguration $table
     *
     * @return TableConfiguration
     */
    public function addTable(TableConfiguration $table)
    {
        return $this->tableConfigurations[$table->getName()] = $table;
    }

    /**
     * @return string
     */
    public function getDumper()
    {
        return $this->get('dumper');
    }

    /**
     * @return string
     */
    public function getFullQualifiedDumperClassName()
    {
        return 'Digilist\\SnakeDumper\\Dumper\\' . $this->getDumper() . 'Dumper';
    }

    /**
     * @return OutputConfiguration
     */
    public function getOutput()
    {
        return $this->outputConfiguration;
    }

    protected function parseConfig(array $dumperConfig)
    {
        // ensure keys exist
        $dumperConfig = array_merge(array(
            'database' => array(),
            'output' => array(),
            'tables' => array(),
        ), $dumperConfig);

        $this->databaseConfiguration = new DatabaseConfiguration($dumperConfig['database']);
        $this->outputConfiguration = new OutputConfiguration($dumperConfig['output']);

        // parse tables
        foreach ($dumperConfig['tables'] as $name => $tableConfig) {
            $this->tableConfigurations[$name] = new TableConfiguration($name, $tableConfig);
        }

        // parse dependent columns
        foreach ($this->tableConfigurations as $table) {
            foreach ($table->getDependencies() as $dependency) {
                if (!array_key_exists($dependency, $this->tableConfigurations)) {
                    $this->tableConfigurations[$dependency] = new TableConfiguration($dependency, array());
                }
            }
            
            foreach ($table->getColumns() as $columnConfig) {
                foreach ($columnConfig->getFilters() as $filter) {
                    if ($filter instanceof DataDependentFilterConfiguration) {
                        $this->tableConfigurations[$filter->getTable()]->addCollectColumn($filter->getColumn());
                    }
                }
            }
        }
    }
}