/**
 * Редактор условий выборки из инфоблока
 */
window.nc_condition_editor_infoblock_query = class extends nc_condition_editor {

    // URLs to fetch miscellaneous data
    dataPath = ADMIN_PATH + 'condition/data/json/';
    urls = {
        SelectSubdivisionList: this.dataPath + 'subdivision_list.php?site_id=%site_id%&infoblock_id=%infoblock_id%', // SelectFromJson
        SelectObjectList: this.dataPath + 'object_list.php?infoblock_id=%infoblock_id%', // SelectFromJson
        SelectObjectPropertyList: this.dataPath + 'component_field_list.php?infoblock_id=%infoblock_id%',
        SelectFromClassifierList: this.dataPath + 'classifier_values.php',
    };

    conditionTypes = {
        // GROUP_OBJECTS //
        object_sub: {
            group: 'GROUP_OBJECTS',
            label: 'TYPE_SUBDIVISION',
            params: [
                this.str('TYPE_SUBDIVISION'),
                this.opEqNe,
                this.makeSubdivisionField('value')
            ]
        },
        object_parentsub: {
            group: 'GROUP_OBJECTS',
            label: 'TYPE_SUBDIVISION_DESCENDANTS',
            params: [
                this.str('TYPE_SUBDIVISION_DESCENDANTS'),
                this.opEqNe,
                this.makeSubdivisionField('value')
            ]
        },
        object: {
            group: 'GROUP_OBJECTS',
            label: 'TYPE_OBJECT',
            params: [
                this.str('TYPE_OBJECT'),
                this.opEqNe,
                this.makeObjectField('value')
            ]
        },
        object_property: {
            group: 'GROUP_OBJECTS',
            label: 'TYPE_OBJECT_FIELD',
            params: [
                this.str('TYPE_OBJECT_FIELD'),
                { name: 'field', type: 'SelectObjectProperty' },
                { type: 'ObjectPropertyOptions' }
            ]
        }
    };

};