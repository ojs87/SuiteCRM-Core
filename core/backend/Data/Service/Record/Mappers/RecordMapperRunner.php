<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2024 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

namespace App\Data\Service\Record\Mappers;

use App\Data\Entity\Record;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;

class RecordMapperRunner implements RecordMapperRunnerInterface
{
    protected BaseFieldMapperRegistry $fieldMapperRegistry;
    protected BaseFieldTypeMapperRegistry $fieldTypeMapperRegistry;
    protected BaseRecordMapperRegistry $recordMapperRegistry;
    protected FieldDefinitionsProviderInterface $fieldDefinitions;


    /**
     * RecordMapperRunner constructor.
     * @param BaseFieldMapperRegistry $fieldMapperRegistry
     * @param BaseFieldTypeMapperRegistry $fieldTypeMapperRegistry
     * @param BaseRecordMapperRegistry $recordMapperRegistry
     * @param FieldDefinitionsProviderInterface $fieldDefinitions
     */
    public function __construct(
        BaseFieldMapperRegistry $fieldMapperRegistry,
        BaseFieldTypeMapperRegistry $fieldTypeMapperRegistry,
        BaseRecordMapperRegistry $recordMapperRegistry,
        FieldDefinitionsProviderInterface $fieldDefinitions
    ) {
        $this->fieldMapperRegistry = $fieldMapperRegistry;
        $this->fieldTypeMapperRegistry = $fieldTypeMapperRegistry;
        $this->recordMapperRegistry = $recordMapperRegistry;
        $this->fieldDefinitions = $fieldDefinitions;
    }

    public function toInternal(Record $record): void
    {
        $this->runMappers($record, 'toInternal');
    }

    public function toExternal(Record $record): void
    {
        $this->runMappers($record, 'toExternal');
    }

    protected function runMappers(Record $record, string $direction): void
    {
        $attributes = $record->getAttributes() ?? [];
        $module = $record->getModule();

        if (empty($attributes) || empty($module)) {
            return;
        }

        $fieldDefinitions = $this->fieldDefinitions->getVardef($module);
        $vardefs = $fieldDefinitions->getVardef();

        $recordMappers = $this->recordMapperRegistry->getMappers($module);
        foreach ($recordMappers as $recordMapper) {
            $recordMapper->$direction($record, $fieldDefinitions);
        }

        $mappedFields = [];

        foreach ($vardefs as $field => $vardef) {
            $mappedFields[$field] = true;

            $this->runMappersForField($vardef, $field, $direction, $record, $fieldDefinitions);
        }

        foreach ($attributes as $field => $attribute) {
            if (!empty($mappedFields[$field])) {
                continue;
            }
            $this->runMappersForField($vardefs[$field] ?? null, $field, $direction, $record, $fieldDefinitions);
        }
    }

    /**
     * @param array|null $vardefs
     * @param string $field
     * @param string $direction
     * @param Record $record
     * @param FieldDefinition $fieldDefinitions
     * @return void
     */
    protected function runMappersForField(
        ?array $vardefs,
        string $field,
        string $direction,
        Record $record,
        FieldDefinition $fieldDefinitions
    ): void
    {
        $fieldVardefs = $vardefs ?? [];
        $type = $fieldVardefs['type'] ?? '';

        if ($type !== '') {
            $fieldTypeMappers = $this->fieldTypeMapperRegistry->getMappers($record->getModule(), $type);
            foreach ($fieldTypeMappers as $fieldTypeMapper) {
                $fieldTypeMapper->$direction($record, $fieldDefinitions, $field);
            }
        }

        $fieldMappers = $this->fieldMapperRegistry->getMappers($record->getModule(), $field);
        foreach ($fieldMappers as $fieldMapper) {
            $fieldMapper->$direction($record, $fieldDefinitions);
        }
    }
}
