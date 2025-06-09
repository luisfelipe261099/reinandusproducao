<?php
// Este arquivo contém as modificações que devem ser feitas no arquivo polos.php
// Adicione estas modificações ao switch case de ações no arquivo polos.php

/*
Adicione estes cases ao switch ($action) no arquivo polos.php:
*/

case 'novo':
    // Exibe o formulário para cadastro de novo polo com tipos
    $titulo_pagina = 'Novo Polo';
    $view = 'novo_com_tipos';
    break;

case 'editar':
    // Exibe o formulário para edição de polo com tipos
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        setMensagem('erro', 'ID do polo não informado.');
        redirect('polos.php');
    }
    
    // Busca os dados do polo
    $sql = "SELECT * FROM polos WHERE id = ?";
    $polo = executarConsulta($db, $sql, [$id]);
    
    if (!$polo) {
        setMensagem('erro', 'Polo não encontrado.');
        redirect('polos.php');
    }
    
    // Busca os tipos de polo associados
    $sql = "SELECT tipo_polo_id FROM polos_tipos WHERE polo_id = ?";
    $tipos_polo_result = executarConsultaAll($db, $sql, [$id]);
    $tipos_polo_selecionados = array_column($tipos_polo_result, 'tipo_polo_id');
    
    // Busca as informações financeiras do polo
    $sql = "SELECT * FROM polos_financeiro WHERE polo_id = ?";
    $financeiro_result = executarConsultaAll($db, $sql, [$id]);
    $financeiro_polo = [];
    
    foreach ($financeiro_result as $item) {
        $financeiro_polo[$item['tipo_polo_id']] = $item;
    }
    
    $titulo_pagina = 'Editar Polo';
    $view = 'editar_com_tipos';
    break;

case 'salvar':
    // Salva os dados do polo com tipos
    include 'views/polos/salvar_com_tipos.php';
    exit;
    break;

case 'financeiro':
    // Exibe as informações financeiras do polo
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        setMensagem('erro', 'ID do polo não informado.');
        redirect('polos.php');
    }
    
    $titulo_pagina = 'Financeiro do Polo';
    $view = 'financeiro';
    break;

case 'editar_financeiro':
    // Exibe o formulário para edição das informações financeiras do polo
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$id) {
        setMensagem('erro', 'ID do polo não informado.');
        redirect('polos.php');
    }
    
    $titulo_pagina = 'Editar Financeiro do Polo';
    $view = 'editar_financeiro';
    break;

case 'salvar_financeiro':
    // Salva as informações financeiras do polo
    include 'views/polos/salvar_financeiro.php';
    exit;
    break;

/*
Adicione também a função para buscar tipos de polos no arquivo polos.php:
*/

// Função para buscar tipos de polos
function buscarTiposPolos($db) {
    $sql = "SELECT id, nome, descricao FROM tipos_polos WHERE status = 'ativo' ORDER BY nome ASC";
    return executarConsultaAll($db, $sql);
}

// Função para buscar configurações financeiras dos tipos de polos
function buscarConfiguracoesFinanceiras($db) {
    $sql = "SELECT tpf.*, tp.nome as tipo_nome 
            FROM tipos_polos_financeiro tpf 
            JOIN tipos_polos tp ON tpf.tipo_polo_id = tp.id";
    $configs = executarConsultaAll($db, $sql);
    
    $resultado = [];
    foreach ($configs as $config) {
        $resultado[$config['tipo_polo_id']] = $config;
    }
    
    return $resultado;
}
