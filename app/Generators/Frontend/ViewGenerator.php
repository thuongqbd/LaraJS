<?php

namespace App\Generators\Frontend;

use App\Generators\BaseGenerator;
use App\Service\FileService;
use App\Service\GeneratorService;
use Carbon\Carbon;

class ViewGenerator extends BaseGenerator
{
    /** @var $service */
    public $serviceGenerator;

    /** @var $service */
    public $serviceFile;

    /** @var string */
    public $path;

    /** @var string */
    public $notDelete;

    /** @var string */
    public $dbType;

    public function __construct($fields, $model)
    {
        $this->serviceGenerator = new GeneratorService();
        $this->serviceFile = new FileService();
        $this->path = config('generator.path.vuejs.views');
        $this->dbType = config('generator.db_type');
        $this->notDelete = config('generator.not_delete.vuejs.views');

        $this->generate($fields, $model);
    }

    private function generate($fields, $model)
    {
        $pathTemplate = 'Views/';
        $templateData = $this->serviceGenerator->get_template('index', $pathTemplate, 'vuejs');
        $templateData = str_replace(
            '{{$CONST_MODEL_CLASS$}}',
            $this->serviceGenerator->modelNameNotPluralFe($model['name']),
            $templateData,
        );
        $templateData = str_replace(
            '{{$TABLE_MODEL_CLASS$}}',
            $this->serviceGenerator->tableNameNotPlural($model['name']),
            $templateData,
        );
        $templateData = str_replace(
            '{{$MODEL_CLASS$}}',
            $this->serviceGenerator->modelNamePlural($model['name']),
            $templateData,
        );
        $templateData = $this->serviceGenerator->replaceNotDelete(
            $this->notDelete['templates'],
            $this->generateHandler($fields),
            5,
            $templateData,
            2,
        );
        $templateData = str_replace('{{$COLUMN_FIELD$}}', $this->generateColumnFields($fields), $templateData);
        $templateData = str_replace(
            $this->notDelete['headings'],
            $this->generateHeadingFields($fields, $model),
            $templateData,
        );
        $templateData = str_replace(
            $this->notDelete['column_classes'],
            $this->generateColumnClassesFields($fields, $model),
            $templateData,
        );
        $templateData = str_replace(
            '{{$SORTABLE_FIELDS$}}',
            $this->generateSortableFields($fields, $model),
            $templateData,
        );
        $folderName = $this->path . $this->serviceGenerator->folderPages($model['name']);
        if (!is_dir($folderName)) {
            mkdir($folderName, 0755, true);
        }

        $fileName = $this->serviceGenerator->folderPages($model['name']) . '/index' . '.vue';
        $this->serviceFile->createFile($this->path, $fileName, $templateData);
    }

    private function generateColumnFields($fields)
    {
        $fieldsGenerate = '';
        foreach ($fields as $index => $field) {
            if ($field['show']) {
                $fieldsGenerate .= "'" . $field['field_name'] . "'" . ', ';
            }
        }
        $fieldsGenerate .= "'created_at', 'actions'";
        return $fieldsGenerate;
    }

    private function generateHeadingFields($fields, $model)
    {
        $fieldsGenerate = [];

        foreach ($fields as $field) {
            if ($field['show']) {
                $fieldsGenerate[] =
                    '"' .
                    $field['field_name'] .
                    '": () => this.$t("table.' .
                    $this->serviceGenerator->tableNameNotPlural($model['name']) .
                    '.' .
                    $field['field_name'] .
                    '")' .
                    ',';
            }
        }
        $fieldsGenerate[] = '"created_at": () => this.$t("date.created_at")';
        return implode($this->serviceGenerator->infy_nl_tab(1, 3), $fieldsGenerate);
    }

    private function generateColumnClassesFields($fields, $model)
    {
        $fieldsGenerate = [];

        foreach ($fields as $index => $field) {
            if ($field['show']) {
                switch ($field['db_type']) {
                    case 'Increments':
                    case $this->dbType['integer']:
                    case $this->dbType['bigInteger']:
                    case $this->dbType['float']:
                    case $this->dbType['double']:
                    case $this->dbType['boolean']:
                    case $this->dbType['date']:
                    case $this->dbType['dateTime']:
                    case $this->dbType['time']:
                    case $this->dbType['year']:
                    case $this->dbType['enum']:
                    case $this->dbType['file']:
                        $fieldName = $field['field_name'];
                        $fieldsGenerate[] = "'$fieldName': 'text-center'" . ',';
                        break;
                }
            }
        }
        if ($this->serviceGenerator->getOptions(config('generator.model.options.sort_deletes'), $model['options'])) {
            $fieldsGenerate[] = "created_at: 'text-center'";
        }

        return implode($this->serviceGenerator->infy_nl_tab(1, 3), $fieldsGenerate);
    }

    private function generateSortableFields($fields, $model)
    {
        $fieldsGenerate = '';
        foreach ($fields as $index => $field) {
            if ($field['sort']) {
                $fieldsGenerate .= "'" . $field['field_name'] . "'" . ', ';
            }
        }
        if ($this->serviceGenerator->getOptions(config('generator.model.options.sort_deletes'), $model['options'])) {
            $fieldsGenerate .= "'created_at'";
        }

        return $fieldsGenerate;
    }

    private function generateHandler($fields)
    {
        $fieldsGenerate = [];
        $pathTemplate = 'Handler/';
        $templateDataLongText = $this->serviceGenerator->get_template('longText', $pathTemplate, 'vuejs');
        $templateDataUploadParse = $this->serviceGenerator->get_template('uploadParse', $pathTemplate, 'vuejs');
        $templateBoolean = $this->serviceGenerator->get_template('boolean', $pathTemplate, 'vuejs');

        foreach ($fields as $index => $field) {
            if ($field['show']) {
                if ($field['db_type'] === $this->dbType['longtext']) {
                    $fieldsGenerate[] = str_replace('{{$FIELD_NAME$}}', $field['field_name'], $templateDataLongText);
                } elseif ($field['db_type'] === $this->dbType['file']) {
                    $fieldsGenerate[] = str_replace('{{$FIELD_NAME$}}', $field['field_name'], $templateDataUploadParse);
                } elseif ($field['db_type'] === $this->dbType['boolean']) {
                    $fieldsGenerate[] = str_replace('{{$FIELD_NAME$}}', $field['field_name'], $templateBoolean);
                }
            }
        }
        return implode($this->serviceGenerator->infy_nl_tab(1, 2, 5), $fieldsGenerate);
    }
}
