<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Sankhya\SankhyaController;
use Illuminate\Http\Request;

class RecoardController extends SankhyaController
{
    
    public function saveRecoardFunction(Request $request)
    {
        $rootEntity            = $request->input('nome_tabela');
        $localFieldsName       = $request->input('nome_fieldsName');

        $localFields = [
            "$localFieldsName" => $request->input('nome_bairro'),
        ];

        $fieldset = [
            'list' => $request->input('nome_fieldset')
        ];

        $result                = $this->saveRecord($rootEntity, $localFields, $fieldset);

        return $result;
    }


    public function loadRecoardFunction(Request $request)
    {
        $rootEntity            = $request->input('rootEntity');
       
        $criteriaExpression    = $request->input('criteriaExpression');
        
        $fieldsetList          = [$request->input('fieldsetList')];

        $result                = $this->loadRecord($rootEntity, $criteriaExpression, $fieldsetList);

        return $result;
    }

}
