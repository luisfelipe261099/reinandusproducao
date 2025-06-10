-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 10/06/2025 às 18:46
-- Versão do servidor: 10.11.10-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u682219090_faciencia_erp`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamentos_pagamentos`
--

CREATE TABLE `agendamentos_pagamentos` (
  `id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `tipo` enum('salario','adiantamento','bonus','ferias','13_salario','outros') NOT NULL DEFAULT 'salario',
  `valor` decimal(10,2) NOT NULL,
  `dia_vencimento` int(11) NOT NULL COMMENT 'Dia do mês para pagamento',
  `forma_pagamento` enum('pix','transferencia','cheque','dinheiro') NOT NULL DEFAULT 'transferencia',
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos`
--

CREATE TABLE `alunos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `nome_social` varchar(150) DEFAULT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `sexo` enum('masculino','feminino','outro') DEFAULT NULL,
  `naturalidade_id` int(10) UNSIGNED DEFAULT NULL,
  `estado_civil_id` int(10) UNSIGNED DEFAULT NULL,
  `situacao_id` int(10) UNSIGNED DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade_id` int(10) UNSIGNED DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `polo_id` int(10) UNSIGNED DEFAULT NULL,
  `curso_id` int(10) UNSIGNED DEFAULT NULL,
  `professor_orientador_id` int(10) UNSIGNED DEFAULT NULL,
  `data_ingresso` date DEFAULT NULL,
  `curso_inicio` date DEFAULT NULL,
  `curso_fim` date DEFAULT NULL,
  `previsao_conclusao` date DEFAULT NULL,
  `mono_titulo` varchar(255) DEFAULT NULL,
  `mono_data` date DEFAULT NULL,
  `mono_nota` decimal(5,2) DEFAULT NULL,
  `mono_prazo` date DEFAULT NULL,
  `status` enum('ativo','trancado','cancelado','formado','desistente') DEFAULT 'ativo',
  `acesso_ava` tinyint(1) NOT NULL DEFAULT 0,
  `primeiro_acesso` tinyint(1) NOT NULL DEFAULT 1,
  `entregou_diploma` tinyint(1) DEFAULT 0,
  `entregou_cpf` tinyint(1) DEFAULT 0,
  `entregou_rg` tinyint(1) DEFAULT 0,
  `bolsa` decimal(10,2) DEFAULT NULL,
  `desconto` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cidade` text NOT NULL,
  `estado` text NOT NULL,
  `turma_id` int(150) NOT NULL,
  `expedidor` text NOT NULL,
  `orgao_expedidor` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos_acesso`
--

CREATE TABLE `alunos_acesso` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expiracao` datetime DEFAULT NULL,
  `ultimo_acesso` datetime DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos_atividades`
--

CREATE TABLE `alunos_atividades` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `descricao` text NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos_preferencias`
--

CREATE TABLE `alunos_preferencias` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `tema` varchar(50) DEFAULT 'light',
  `notificacoes_email` tinyint(1) DEFAULT 1,
  `notificacoes_sistema` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos_sessoes`
--

CREATE TABLE `alunos_sessoes` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `areas_conhecimento`
--

CREATE TABLE `areas_conhecimento` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_acessos`
--

CREATE TABLE `ava_acessos` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `data_acesso` datetime NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `pagina` varchar(255) DEFAULT NULL,
  `tempo_sessao` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_aulas`
--

CREATE TABLE `ava_aulas` (
  `id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('video','texto','quiz','arquivo','link') NOT NULL,
  `conteudo` text DEFAULT NULL,
  `url_video` varchar(255) DEFAULT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `duracao` int(11) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `duracao_minutos` int(11) DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_categorias`
--

CREATE TABLE `ava_categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `cor` varchar(20) DEFAULT '#6A5ACD',
  `icone` varchar(50) DEFAULT 'fas fa-book',
  `ordem` int(11) NOT NULL DEFAULT 0,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_certificados`
--

CREATE TABLE `ava_certificados` (
  `id` int(11) NOT NULL,
  `matricula_id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `data_emissao` datetime NOT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_cursos`
--

CREATE TABLE `ava_cursos` (
  `id` int(11) UNSIGNED NOT NULL,
  `polo_id` int(11) UNSIGNED NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `carga_horaria` int(11) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `nivel` enum('basico','intermediario','avancado') DEFAULT 'basico',
  `imagem` varchar(255) DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT NULL,
  `preco_promocional` decimal(10,2) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `requisitos` text DEFAULT NULL,
  `video_apresentacao` varchar(255) DEFAULT NULL,
  `objetivos` text DEFAULT NULL,
  `metodologia` text DEFAULT NULL,
  `avaliacao` text DEFAULT NULL,
  `certificacao` text DEFAULT NULL,
  `destaque` tinyint(1) NOT NULL DEFAULT 0,
  `visibilidade` enum('publico','privado') NOT NULL DEFAULT 'publico',
  `publico_alvo` text DEFAULT NULL,
  `pre_requisitos` text DEFAULT NULL,
  `status` enum('rascunho','revisao','publicado','arquivado') DEFAULT 'rascunho',
  `data_publicacao` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_imagens`
--

CREATE TABLE `ava_imagens` (
  `id` int(11) NOT NULL,
  `polo_id` int(11) NOT NULL,
  `aula_id` int(11) DEFAULT NULL,
  `arquivo_path` varchar(255) NOT NULL,
  `arquivo_nome` varchar(255) NOT NULL,
  `arquivo_tipo` varchar(100) NOT NULL,
  `arquivo_tamanho` int(11) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_materiais`
--

CREATE TABLE `ava_materiais` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('pdf','doc','xls','ppt','zip','imagem','audio','video','link') NOT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_matriculas`
--

CREATE TABLE `ava_matriculas` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `data_matricula` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('ativo','concluido','cancelado','trancado') DEFAULT 'ativo',
  `progresso` int(11) NOT NULL DEFAULT 0,
  `data_conclusao` datetime DEFAULT NULL,
  `nota_final` decimal(5,2) DEFAULT NULL,
  `certificado_emitido` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_modulos`
--

CREATE TABLE `ava_modulos` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) UNSIGNED NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_polos_acesso`
--

CREATE TABLE `ava_polos_acesso` (
  `id` int(10) UNSIGNED NOT NULL,
  `polo_id` int(10) UNSIGNED NOT NULL,
  `liberado` tinyint(1) NOT NULL DEFAULT 0,
  `data_liberacao` datetime DEFAULT NULL,
  `liberado_por` int(10) UNSIGNED DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_progresso`
--

CREATE TABLE `ava_progresso` (
  `id` int(11) NOT NULL,
  `matricula_id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `concluido` tinyint(1) NOT NULL DEFAULT 0,
  `data_conclusao` datetime DEFAULT NULL,
  `tempo_gasto` int(11) DEFAULT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `pontuacao` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_progresso_aulas`
--

CREATE TABLE `ava_progresso_aulas` (
  `id` int(11) NOT NULL,
  `matricula_id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `status` enum('nao_iniciada','em_andamento','concluida') DEFAULT 'nao_iniciada',
  `data_inicio` datetime DEFAULT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `tempo_total_segundos` int(11) DEFAULT 0,
  `nota` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_questoes`
--

CREATE TABLE `ava_questoes` (
  `id` int(11) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `pergunta` text NOT NULL,
  `tipo` enum('multipla_escolha','verdadeiro_falso','resposta_curta','correspondencia') NOT NULL,
  `opcoes` text DEFAULT NULL,
  `resposta_correta` text NOT NULL,
  `explicacao` text DEFAULT NULL,
  `pontos` int(11) NOT NULL DEFAULT 1,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ava_respostas_alunos`
--

CREATE TABLE `ava_respostas_alunos` (
  `id` int(11) NOT NULL,
  `progresso_aula_id` int(11) NOT NULL,
  `questao_id` int(11) NOT NULL,
  `resposta` text NOT NULL,
  `correta` tinyint(1) NOT NULL DEFAULT 0,
  `pontos_obtidos` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `boletos`
--

CREATE TABLE `boletos` (
  `id` int(11) NOT NULL,
  `tipo_entidade` enum('aluno','polo','avulso') NOT NULL,
  `entidade_id` int(11) DEFAULT NULL,
  `nome_pagador` varchar(255) NOT NULL,
  `cpf_pagador` varchar(20) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `uf` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_emissao` date NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `status` enum('pendente','pago','cancelado','vencido') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `nosso_numero` varchar(50) DEFAULT NULL,
  `linha_digitavel` varchar(100) DEFAULT NULL,
  `codigo_barras` varchar(100) DEFAULT NULL,
  `url_boleto` varchar(255) DEFAULT NULL,
  `grupo_boletos` varchar(50) DEFAULT NULL,
  `mensalidade_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `data_cancelamento` date NOT NULL,
  `api_ambiente` text NOT NULL,
  `api_tipo` varchar(50) DEFAULT 'cash_management' COMMENT 'Tipo de API usada para gerar o boleto (cash_management ou cobranca)',
  `api_token_id` int(150) NOT NULL,
  `api_request_data` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `boletos_historico`
--

CREATE TABLE `boletos_historico` (
  `id` int(11) NOT NULL,
  `boleto_id` int(11) NOT NULL,
  `acao` varchar(50) NOT NULL COMMENT 'cancelamento, cancelamento_local, emissao, pagamento, etc',
  `data` datetime NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `detalhes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias_chamados`
--

CREATE TABLE `categorias_chamados` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('interno','polo') NOT NULL COMMENT 'interno: para funcionários, polo: para polos',
  `requer_aprovacao` tinyint(1) NOT NULL DEFAULT 0,
  `departamento_responsavel` enum('secretaria','financeiro','suporte','diretoria') DEFAULT NULL,
  `cor` varchar(7) DEFAULT '#3498db',
  `icone` varchar(50) DEFAULT 'ticket',
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `ordem` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias_financeiras`
--

CREATE TABLE `categorias_financeiras` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('receita','despesa') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chamados`
--

CREATE TABLE `chamados` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) NOT NULL COMMENT 'Código único do chamado (ex: TICK-2024-0001)',
  `titulo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `tipo` enum('interno','polo') NOT NULL COMMENT 'interno: para funcionários, polo: para polos',
  `prioridade` enum('baixa','media','alta','urgente') NOT NULL DEFAULT 'media',
  `status` enum('aberto','em_andamento','aguardando_resposta','aguardando_aprovacao','resolvido','cancelado','fechado') NOT NULL DEFAULT 'aberto',
  `solicitante_id` int(10) UNSIGNED NOT NULL COMMENT 'ID do usuário que abriu o chamado',
  `responsavel_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID do usuário responsável pelo atendimento',
  `departamento` enum('secretaria','financeiro','suporte','diretoria') DEFAULT NULL,
  `polo_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID do polo relacionado (se aplicável)',
  `aluno_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID do aluno relacionado (se aplicável)',
  `data_abertura` datetime NOT NULL,
  `data_ultima_atualizacao` datetime DEFAULT NULL,
  `data_fechamento` datetime DEFAULT NULL,
  `tempo_resolucao` int(11) DEFAULT NULL COMMENT 'Tempo de resolução em minutos',
  `avaliacao` int(11) DEFAULT NULL COMMENT 'Avaliação de 1 a 5 estrelas',
  `comentario_avaliacao` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chamados_alunos`
--

CREATE TABLE `chamados_alunos` (
  `id` int(11) UNSIGNED NOT NULL,
  `chamado_id` int(11) UNSIGNED NOT NULL,
  `aluno_id` int(11) UNSIGNED NOT NULL,
  `documento_gerado` tinyint(1) NOT NULL DEFAULT 0,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `data_geracao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chamados_anexos`
--

CREATE TABLE `chamados_anexos` (
  `id` int(11) NOT NULL,
  `chamado_id` int(11) DEFAULT NULL,
  `resposta_id` int(11) DEFAULT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `tipo_arquivo` varchar(100) DEFAULT NULL,
  `tamanho_arquivo` int(11) DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chamados_documentos`
--

CREATE TABLE `chamados_documentos` (
  `id` int(11) NOT NULL,
  `chamado_id` int(11) NOT NULL,
  `tipo_documento_id` int(10) UNSIGNED NOT NULL,
  `aluno_id` int(10) UNSIGNED NOT NULL,
  `polo_id` int(10) UNSIGNED NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `finalidade` text DEFAULT NULL,
  `status` enum('solicitado','processando','pronto','entregue','cancelado') DEFAULT 'solicitado',
  `valor_unitario` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(10,2) DEFAULT NULL,
  `pago` tinyint(1) DEFAULT 0,
  `data_pagamento` datetime DEFAULT NULL,
  `documento_gerado_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Acionadores `chamados_documentos`
--
DELIMITER $$
CREATE TRIGGER `after_chamados_documentos_insert` AFTER INSERT ON `chamados_documentos` FOR EACH ROW BEGIN
    
    UPDATE polos_financeiro
    SET documentos_disponiveis = documentos_disponiveis - NEW.quantidade,
        documentos_emitidos = documentos_emitidos + NEW.quantidade,
        updated_at = NOW()
    WHERE polo_id = NEW.polo_id AND 
          documentos_disponiveis >= NEW.quantidade;
          
    
    UPDATE polos
    SET documentos_emitidos = documentos_emitidos + NEW.quantidade,
        updated_at = NOW()
    WHERE id = NEW.polo_id;
    
    
    INSERT INTO polos_financeiro_historico (
        polo_id,
        tipo_polo_id,
        tipo_transacao,
        valor,
        quantidade,
        data_transacao,
        descricao,
        usuario_id,
        created_at
    )
    SELECT 
        NEW.polo_id,
        pt.tipo_polo_id,
        'documento',
        COALESCE(pf.valor_por_documento, tpf.valor_documento),
        NEW.quantidade,
        NOW(),
        CONCAT('Solicitação de documento via chamado #', NEW.chamado_id),
        (SELECT solicitante_id FROM chamados WHERE id = NEW.chamado_id),
        NOW()
    FROM polos_tipos pt
    JOIN tipos_polos_financeiro tpf ON pt.tipo_polo_id = tpf.tipo_polo_id
    LEFT JOIN polos_financeiro pf ON pf.polo_id = NEW.polo_id AND pf.tipo_polo_id = pt.tipo_polo_id
    WHERE pt.polo_id = NEW.polo_id
    LIMIT 1;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_chamados_documentos_update` AFTER UPDATE ON `chamados_documentos` FOR EACH ROW BEGIN
    IF NEW.status = 'cancelado' AND OLD.status != 'cancelado' THEN
        
        UPDATE polos_financeiro
        SET documentos_disponiveis = documentos_disponiveis + NEW.quantidade,
            documentos_emitidos = documentos_emitidos - NEW.quantidade,
            updated_at = NOW()
        WHERE polo_id = NEW.polo_id;
        
        
        UPDATE polos
        SET documentos_emitidos = documentos_emitidos - NEW.quantidade,
            updated_at = NOW()
        WHERE id = NEW.polo_id;
        
        
        INSERT INTO polos_financeiro_historico (
            polo_id,
            tipo_polo_id,
            tipo_transacao,
            valor,
            quantidade,
            data_transacao,
            descricao,
            usuario_id,
            created_at
        )
        SELECT 
            NEW.polo_id,
            pt.tipo_polo_id,
            'documento',
            -COALESCE(pf.valor_por_documento, tpf.valor_documento),
            NEW.quantidade,
            NOW(),
            CONCAT('Cancelamento de solicitação de documento via chamado #', NEW.chamado_id),
            (SELECT solicitante_id FROM chamados WHERE id = NEW.chamado_id),
            NOW()
        FROM polos_tipos pt
        JOIN tipos_polos_financeiro tpf ON pt.tipo_polo_id = tpf.tipo_polo_id
        LEFT JOIN polos_financeiro pf ON pf.polo_id = NEW.polo_id AND pf.tipo_polo_id = pt.tipo_polo_id
        WHERE pt.polo_id = NEW.polo_id
        LIMIT 1;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chamados_historico`
--

CREATE TABLE `chamados_historico` (
  `id` int(11) UNSIGNED NOT NULL,
  `chamado_id` int(11) UNSIGNED NOT NULL,
  `usuario_id` int(11) UNSIGNED NOT NULL,
  `acao` varchar(50) NOT NULL,
  `descricao` text NOT NULL,
  `data_hora` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chamados_respostas`
--

CREATE TABLE `chamados_respostas` (
  `id` int(11) NOT NULL,
  `chamado_id` int(11) NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `mensagem` text NOT NULL,
  `tipo` enum('resposta','nota_interna','alteracao_status','sistema') NOT NULL DEFAULT 'resposta',
  `visivel_solicitante` tinyint(1) NOT NULL DEFAULT 1,
  `data_resposta` datetime NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cidades`
--

CREATE TABLE `cidades` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `estado_id` int(10) UNSIGNED NOT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cobranca_polos`
--

CREATE TABLE `cobranca_polos` (
  `id` int(11) NOT NULL,
  `polo_id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `mes_referencia` date NOT NULL,
  `tipo_cobranca` enum('mensalidade','taxa','outros') NOT NULL DEFAULT 'mensalidade',
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `status` enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `valor` text NOT NULL,
  `descricao` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_documentos`
--

CREATE TABLE `configuracoes_documentos` (
  `id` int(11) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_sistema`
--

CREATE TABLE `configuracoes_sistema` (
  `id` int(10) UNSIGNED NOT NULL,
  `chave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('string','numero','booleano','json') NOT NULL DEFAULT 'string',
  `descricao` text DEFAULT NULL,
  `grupo` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_bancarias`
--

CREATE TABLE `contas_bancarias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `agencia` varchar(20) DEFAULT NULL,
  `conta` varchar(20) DEFAULT NULL,
  `tipo` enum('corrente','poupanca','investimento','caixa') NOT NULL DEFAULT 'corrente',
  `saldo_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldo_atual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_saldo` date NOT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_pagar`
--

CREATE TABLE `contas_pagar` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `fornecedor_nome` varchar(100) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `status` enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `comprovante_path` varchar(255) DEFAULT NULL,
  `transacao_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_pagar_rh`
--

CREATE TABLE `contas_pagar_rh` (
  `id` int(11) NOT NULL,
  `pagamento_id` int(11) NOT NULL,
  `conta_pagar_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_receber`
--

CREATE TABLE `contas_receber` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_recebimento` date DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `cliente_nome` varchar(100) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `forma_recebimento` varchar(50) DEFAULT NULL,
  `status` enum('pendente','recebido','cancelado') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `comprovante_path` varchar(255) DEFAULT NULL,
  `transacao_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cursos`
--

CREATE TABLE `cursos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `sigla` varchar(10) DEFAULT NULL,
  `area_conhecimento_id` int(10) UNSIGNED DEFAULT NULL,
  `grupo_id` int(10) UNSIGNED DEFAULT NULL,
  `coordenador_id` int(10) UNSIGNED DEFAULT NULL,
  `carga_horaria` int(11) NOT NULL,
  `descricao` text DEFAULT NULL,
  `objetivo` text DEFAULT NULL,
  `modalidade` enum('presencial','ead','hibrido') NOT NULL,
  `nivel` enum('graduacao','pos_graduacao','extensao','tecnico') NOT NULL,
  `status` enum('ativo','inativo','em_desenvolvimento') DEFAULT 'ativo',
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `polo_id` int(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cursos_backup`
--

CREATE TABLE `cursos_backup` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `sigla` varchar(10) DEFAULT NULL,
  `area_conhecimento_id` int(10) UNSIGNED DEFAULT NULL,
  `grupo_id` int(10) UNSIGNED DEFAULT NULL,
  `coordenador_id` int(10) UNSIGNED DEFAULT NULL,
  `carga_horaria` int(11) NOT NULL,
  `descricao` text DEFAULT NULL,
  `objetivo` text DEFAULT NULL,
  `modalidade` enum('presencial','ead','hibrido') NOT NULL,
  `nivel` enum('graduacao','pos_graduacao','extensao','tecnico') NOT NULL,
  `status` enum('ativo','inativo','em_desenvolvimento') DEFAULT 'ativo',
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `polo_id` int(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `disciplinas`
--

CREATE TABLE `disciplinas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `professor_padrao_id` int(10) UNSIGNED DEFAULT NULL,
  `carga_horaria` int(11) NOT NULL,
  `ementa` text DEFAULT NULL,
  `bibliografia` text DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `periodo` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos`
--

CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `tipo_documento_id` int(10) UNSIGNED NOT NULL,
  `aluno_id` int(10) UNSIGNED DEFAULT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `data_emissao` date DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `orgao_emissor` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `arquivo_nome` varchar(255) DEFAULT NULL,
  `arquivo_tipo` varchar(100) DEFAULT NULL,
  `arquivo_tamanho` int(11) DEFAULT NULL,
  `status` enum('ativo','inativo','cancelado') NOT NULL DEFAULT 'ativo',
  `id_legado` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_alunos`
--

CREATE TABLE `documentos_alunos` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `tipo_documento_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `data_upload` datetime NOT NULL DEFAULT current_timestamp(),
  `data_validade` date DEFAULT NULL,
  `numero_documento` varchar(100) DEFAULT NULL,
  `orgao_emissor` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_emitidos`
--

CREATE TABLE `documentos_emitidos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `solicitacao_id` int(10) UNSIGNED NOT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `numero_documento` varchar(50) DEFAULT NULL,
  `data_emissao` date NOT NULL,
  `status` enum('ativo','cancelado') DEFAULT 'ativo',
  `aluno_id` int(220) NOT NULL,
  `matricula_id` int(220) NOT NULL,
  `curso_id` int(220) NOT NULL,
  `polo_id` int(220) NOT NULL,
  `tipo_documento_id` int(220) NOT NULL,
  `data_validade` date NOT NULL,
  `codigo_verificacao` int(220) NOT NULL,
  `data_solicitacao` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `emails_enviados`
--

CREATE TABLE `emails_enviados` (
  `id` int(11) NOT NULL,
  `solicitacao_id` int(11) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `arquivo_nome` varchar(255) DEFAULT NULL,
  `drive_link` text DEFAULT NULL,
  `data_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estados`
--

CREATE TABLE `estados` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `sigla` varchar(2) NOT NULL,
  `pais` varchar(50) DEFAULT 'Brasil',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `folha_pagamento`
--

CREATE TABLE `folha_pagamento` (
  `id` int(11) NOT NULL,
  `mes_referencia` int(11) NOT NULL,
  `ano_referencia` int(11) NOT NULL,
  `data_geracao` datetime NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `status` enum('aberta','fechada','paga') NOT NULL DEFAULT 'aberta',
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `folha_pagamento_itens`
--

CREATE TABLE `folha_pagamento_itens` (
  `id` int(11) NOT NULL,
  `folha_id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `inss` decimal(10,2) NOT NULL DEFAULT 0.00,
  `irrf` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fgts` decimal(10,2) NOT NULL DEFAULT 0.00,
  `outros_descontos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `outros_proventos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_liquido` decimal(10,2) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionarios`
--

CREATE TABLE `funcionarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_admissao` date NOT NULL,
  `data_demissao` date DEFAULT NULL,
  `cargo` varchar(100) NOT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `salario` decimal(10,2) NOT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `agencia` varchar(20) DEFAULT NULL,
  `conta` varchar(20) DEFAULT NULL,
  `tipo_conta` varchar(20) DEFAULT NULL,
  `pix` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `dia_pagamento` int(11) DEFAULT NULL,
  `forma_pagamento` varchar(20) DEFAULT NULL,
  `gerar_pagamento_automatico` tinyint(1) NOT NULL DEFAULT 0,
  `endereco` text DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `status` enum('ativo','inativo','afastado','ferias') NOT NULL DEFAULT 'ativo',
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `grupos_academicos`
--

CREATE TABLE `grupos_academicos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `lancamentos_financeiros`
--

CREATE TABLE `lancamentos_financeiros` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `polo_id` int(10) UNSIGNED DEFAULT NULL,
  `aluno_id` int(10) UNSIGNED DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `categoria_id` int(10) UNSIGNED NOT NULL,
  `plano_conta_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('receita','despesa','transferencia') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `acrescimo` decimal(10,2) DEFAULT 0.00,
  `data_lancamento` date NOT NULL,
  `data_vencimento` date DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `status` enum('pendente','pago','parcial','cancelado','agendado') DEFAULT 'pendente',
  `descricao` text DEFAULT NULL,
  `forma_pagamento` enum('dinheiro','transferencia_bancaria','cartao_credito','cartao_debito','boleto','pix','cheque') DEFAULT NULL,
  `documento_referencia` varchar(50) DEFAULT NULL,
  `numero_parcela` int(11) DEFAULT 1,
  `total_parcelas` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `modulo` varchar(50) NOT NULL,
  `acao` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ip_origem` varchar(45) DEFAULT NULL,
  `dispositivo` varchar(100) DEFAULT NULL,
  `objeto_id` int(10) UNSIGNED DEFAULT NULL,
  `objeto_tipo` varchar(50) DEFAULT NULL,
  `dados_antigos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_antigos`)),
  `dados_novos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_novos`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mapeamento_ids_legados`
--

CREATE TABLE `mapeamento_ids_legados` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_novo` int(10) UNSIGNED NOT NULL,
  `id_antigo` varchar(100) NOT NULL,
  `tabela` varchar(100) NOT NULL,
  `sistema_origem` varchar(50) DEFAULT 'legado',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `matriculas`
--

CREATE TABLE `matriculas` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero_matricula` varchar(50) DEFAULT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `aluno_id` int(10) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `polo_id` int(10) UNSIGNED DEFAULT NULL,
  `turma_id` int(10) UNSIGNED DEFAULT NULL,
  `data_matricula` date NOT NULL,
  `data_conclusao` date DEFAULT NULL,
  `status` enum('ativo','trancado','concluído','cancelado') DEFAULT 'ativo',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `forma_pagamento` text NOT NULL,
  `valor_total` int(200) NOT NULL,
  `observacoes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensalidades_alunos`
--

CREATE TABLE `mensalidades_alunos` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `mes_referencia` date NOT NULL,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `multa` decimal(10,2) DEFAULT 0.00,
  `juros` decimal(10,2) DEFAULT 0.00,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `status` enum('pendente','pago','cancelado','isento') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `modulos`
--

CREATE TABLE `modulos` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `icone` varchar(50) NOT NULL,
  `ordem` int(11) NOT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notas_disciplinas`
--

CREATE TABLE `notas_disciplinas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `disciplina_id` int(10) UNSIGNED NOT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `frequencia` decimal(5,2) DEFAULT NULL,
  `horas_aula` decimal(10,2) DEFAULT NULL,
  `data_lancamento` date DEFAULT NULL,
  `situacao` enum('cursando','aprovado','reprovado') DEFAULT 'cursando',
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `funcionario_id` int(11) NOT NULL,
  `tipo` enum('salario','adiantamento','bonus','ferias','13_salario','outros') NOT NULL DEFAULT 'salario',
  `valor` decimal(10,2) NOT NULL,
  `data_pagamento` date NOT NULL,
  `data_competencia` date NOT NULL,
  `forma_pagamento` enum('pix','transferencia','cheque','dinheiro') NOT NULL DEFAULT 'transferencia',
  `status` enum('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `comprovante` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `permissoes`
--

CREATE TABLE `permissoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `modulo` enum('alunos','cursos','disciplinas','matriculas','polos','financeiro','documentos','usuarios','relatorios','sistema','chamados') NOT NULL,
  `nivel_acesso` enum('nenhum','visualizar','criar','editar','excluir','total') DEFAULT 'nenhum',
  `restricoes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`restricoes`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `plano_contas`
--

CREATE TABLE `plano_contas` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `tipo` enum('receita','despesa','ambos') NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `status` enum('ativo','inativo') NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `polos`
--

CREATE TABLE `polos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `razao_social` varchar(250) DEFAULT NULL,
  `cnpj` text DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `cep` varchar(20) DEFAULT NULL,
  `cidade_ibge` int(11) DEFAULT NULL,
  `cidade_id` int(10) UNSIGNED DEFAULT NULL,
  `responsavel_id` int(11) UNSIGNED DEFAULT NULL,
  `data_inicio_parceria` date DEFAULT NULL,
  `data_fim_contrato` date DEFAULT NULL,
  `status_contrato` enum('ativo','suspenso','encerrado') DEFAULT 'ativo',
  `limite_documentos` int(11) DEFAULT 100,
  `documentos_emitidos` int(11) DEFAULT 0,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `responsavel` text NOT NULL,
  `mec` varchar(255) DEFAULT NULL COMMENT 'Nome do polo registrado no MEC'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `polos_financeiro`
--

CREATE TABLE `polos_financeiro` (
  `id` int(11) NOT NULL,
  `polo_id` int(11) NOT NULL,
  `tipo_polo_id` int(11) NOT NULL,
  `data_inicial` date DEFAULT NULL,
  `vigencia_contrato_meses` int(11) DEFAULT NULL,
  `vencimento_contrato` date DEFAULT NULL,
  `vigencia_pacote_setup` int(11) DEFAULT NULL,
  `vencimento_pacote_setup` date DEFAULT NULL,
  `valor_unitario_normal` decimal(10,2) DEFAULT NULL,
  `quantidade_contratada` int(11) DEFAULT NULL,
  `data_primeira_parcela` date DEFAULT NULL,
  `data_ultima_parcela` date DEFAULT NULL,
  `quantidade_parcelas` int(11) DEFAULT NULL,
  `valor_previsto` decimal(10,2) DEFAULT NULL,
  `taxa_inicial` decimal(10,2) DEFAULT NULL,
  `valor_por_documento` decimal(10,2) DEFAULT NULL,
  `taxa_inicial_paga` tinyint(1) NOT NULL DEFAULT 0,
  `data_pagamento_taxa` date DEFAULT NULL,
  `pacotes_adquiridos` int(11) NOT NULL DEFAULT 0,
  `documentos_disponiveis` int(11) NOT NULL DEFAULT 0,
  `documentos_emitidos` int(11) NOT NULL DEFAULT 0,
  `valor_total_pago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `observacoes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `polos_financeiro_historico`
--

CREATE TABLE `polos_financeiro_historico` (
  `id` int(11) NOT NULL,
  `polo_id` int(11) NOT NULL,
  `tipo_polo_id` int(11) NOT NULL,
  `tipo_transacao` enum('taxa_inicial','pacote','documento','outro') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `data_transacao` date NOT NULL,
  `descricao` text DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `polos_tipos`
--

CREATE TABLE `polos_tipos` (
  `id` int(11) NOT NULL,
  `polo_id` int(11) NOT NULL,
  `tipo_polo_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `polos_tipos_backup`
--

CREATE TABLE `polos_tipos_backup` (
  `id` int(11) NOT NULL,
  `polo_id` int(11) NOT NULL,
  `tipo_polo_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `professores`
--

CREATE TABLE `professores` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `formacao` varchar(100) DEFAULT NULL,
  `titulacao` enum('graduacao','especializacao','mestrado','doutorado','pos_doutorado') DEFAULT NULL,
  `area_atuacao` varchar(100) DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `id_legado` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes_documentos`
--

CREATE TABLE `solicitacoes_documentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `aluno_id` int(10) UNSIGNED NOT NULL,
  `polo_id` int(10) UNSIGNED NOT NULL,
  `tipo_documento_id` int(10) UNSIGNED NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `finalidade` text DEFAULT NULL,
  `status` enum('solicitado','processando','pronto','entregue','cancelado') DEFAULT 'solicitado',
  `valor_total` decimal(10,2) DEFAULT NULL,
  `pago` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `observacoes` text NOT NULL,
  `data_solicitacao` date NOT NULL,
  `solicitante_id` int(200) NOT NULL,
  `documento_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes_s`
--

CREATE TABLE `solicitacoes_s` (
  `id` int(11) NOT NULL,
  `protocolo` varchar(30) NOT NULL,
  `nome_empresa` varchar(255) NOT NULL,
  `cnpj` varchar(20) NOT NULL,
  `nome_solicitante` varchar(255) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `link_planilha` varchar(255) NOT NULL,
  `tipo_solicitacao` varchar(50) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `observacao` text DEFAULT NULL,
  `data_solicitacao` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(30) NOT NULL DEFAULT 'Pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_documentos`
--

CREATE TABLE `tipos_documentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `template` text DEFAULT NULL,
  `campos_obrigatorios` text DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL,
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL,
  `prazo` int(11) NOT NULL COMMENT 'Prazo em dias para emissão'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_documentos_pessoais`
--

CREATE TABLE `tipos_documentos_pessoais` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `obrigatorio` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_polos`
--

CREATE TABLE `tipos_polos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_polos_financeiro`
--

CREATE TABLE `tipos_polos_financeiro` (
  `id` int(11) NOT NULL,
  `tipo_polo_id` int(11) NOT NULL,
  `taxa_inicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `taxa_por_documento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pacote_documentos` int(11) NOT NULL DEFAULT 0,
  `valor_pacote` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descricao` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes`
--

CREATE TABLE `transacoes` (
  `id` int(11) NOT NULL,
  `tipo` enum('receita','despesa','transferencia') NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_transacao` date NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `conta_id` int(11) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `status` enum('efetivada','pendente','cancelada') NOT NULL DEFAULT 'efetivada',
  `observacoes` text DEFAULT NULL,
  `comprovante_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes_financeiras`
--

CREATE TABLE `transacoes_financeiras` (
  `id` int(11) NOT NULL,
  `tipo` enum('receita','despesa','transferencia') NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_transacao` date NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `conta_bancaria_id` int(11) DEFAULT NULL,
  `conta_destino_id` int(11) DEFAULT NULL,
  `forma_pagamento` varchar(50) DEFAULT NULL,
  `referencia_tipo` enum('conta_pagar','conta_receber','folha_pagamento','outros') DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `status` enum('efetivada','pendente','cancelada') NOT NULL DEFAULT 'efetivada',
  `observacoes` text DEFAULT NULL,
  `comprovante_path` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `polo_id` int(10) UNSIGNED NOT NULL,
  `professor_coordenador_id` int(10) UNSIGNED DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `turno` enum('manha','tarde','noite','integral') NOT NULL,
  `vagas_total` int(11) NOT NULL,
  `vagas_preenchidas` int(11) DEFAULT 0,
  `status` enum('planejada','em_andamento','concluida','cancelada') DEFAULT 'planejada',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `observacoes` text NOT NULL,
  `carga_horaria` int(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas_backup`
--

CREATE TABLE `turmas_backup` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `polo_id` int(10) UNSIGNED NOT NULL,
  `professor_coordenador_id` int(10) UNSIGNED DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `turno` enum('manha','tarde','noite','integral') NOT NULL,
  `vagas_total` int(11) NOT NULL,
  `vagas_preenchidas` int(11) DEFAULT 0,
  `status` enum('planejada','em_andamento','concluida','cancelada') DEFAULT 'planejada',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_legado` int(10) UNSIGNED DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('admin_master','diretoria','secretaria_academica','secretaria_documentos','financeiro','polo','professor','aluno') NOT NULL,
  `status` enum('ativo','inativo','bloqueado') DEFAULT 'ativo',
  `ultimo_acesso` datetime DEFAULT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expiracao` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agendamentos_pagamentos`
--
ALTER TABLE `agendamentos_pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices de tabela `alunos`
--
ALTER TABLE `alunos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `cidade_id` (`cidade_id`),
  ADD KEY `professor_orientador_id` (`professor_orientador_id`),
  ADD KEY `idx_id_legado` (`id_legado`),
  ADD KEY `idx_naturalidade` (`naturalidade_id`),
  ADD KEY `idx_estado_civil` (`estado_civil_id`),
  ADD KEY `idx_situacao` (`situacao_id`),
  ADD KEY `idx_alunos_polo_id` (`polo_id`),
  ADD KEY `idx_alunos_curso_id` (`curso_id`),
  ADD KEY `idx_alunos_turma_id` (`turma_id`),
  ADD KEY `idx_alunos_status` (`status`);

--
-- Índices de tabela `alunos_acesso`
--
ALTER TABLE `alunos_acesso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `alunos_atividades`
--
ALTER TABLE `alunos_atividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `alunos_preferencias`
--
ALTER TABLE `alunos_preferencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `alunos_sessoes`
--
ALTER TABLE `alunos_sessoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `token` (`token`);

--
-- Índices de tabela `areas_conhecimento`
--
ALTER TABLE `areas_conhecimento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `ava_acessos`
--
ALTER TABLE `ava_acessos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aluno_id` (`aluno_id`),
  ADD KEY `idx_data_acesso` (`data_acesso`);

--
-- Índices de tabela `ava_aulas`
--
ALTER TABLE `ava_aulas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modulo_id` (`modulo_id`);

--
-- Índices de tabela `ava_categorias`
--
ALTER TABLE `ava_categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `ava_certificados`
--
ALTER TABLE `ava_certificados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula_id` (`matricula_id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `ava_cursos`
--
ALTER TABLE `ava_cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `status` (`status`);

--
-- Índices de tabela `ava_imagens`
--
ALTER TABLE `ava_imagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `aula_id` (`aula_id`);

--
-- Índices de tabela `ava_materiais`
--
ALTER TABLE `ava_materiais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aula_id` (`aula_id`);

--
-- Índices de tabela `ava_matriculas`
--
ALTER TABLE `ava_matriculas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aluno_curso` (`aluno_id`,`curso_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `curso_id` (`curso_id`);

--
-- Índices de tabela `ava_modulos`
--
ALTER TABLE `ava_modulos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curso_id` (`curso_id`);

--
-- Índices de tabela `ava_polos_acesso`
--
ALTER TABLE `ava_polos_acesso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_polo_id` (`polo_id`),
  ADD KEY `idx_liberado_por` (`liberado_por`);

--
-- Índices de tabela `ava_progresso`
--
ALTER TABLE `ava_progresso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_matricula_aula` (`matricula_id`,`aula_id`),
  ADD KEY `idx_matricula_id` (`matricula_id`),
  ADD KEY `idx_aula_id` (`aula_id`);

--
-- Índices de tabela `ava_progresso_aulas`
--
ALTER TABLE `ava_progresso_aulas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula_aula` (`matricula_id`,`aula_id`),
  ADD KEY `matricula_id` (`matricula_id`),
  ADD KEY `aula_id` (`aula_id`);

--
-- Índices de tabela `ava_questoes`
--
ALTER TABLE `ava_questoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aula_id` (`aula_id`);

--
-- Índices de tabela `ava_respostas_alunos`
--
ALTER TABLE `ava_respostas_alunos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `progresso_questao` (`progresso_aula_id`,`questao_id`),
  ADD KEY `progresso_aula_id` (`progresso_aula_id`),
  ADD KEY `questao_id` (`questao_id`);

--
-- Índices de tabela `boletos`
--
ALTER TABLE `boletos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_entidade` (`tipo_entidade`,`entidade_id`),
  ADD KEY `status` (`status`),
  ADD KEY `data_vencimento` (`data_vencimento`),
  ADD KEY `grupo_boletos` (`grupo_boletos`),
  ADD KEY `mensalidade_id` (`mensalidade_id`),
  ADD KEY `mensalidade_id_2` (`mensalidade_id`);

--
-- Índices de tabela `boletos_historico`
--
ALTER TABLE `boletos_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `boleto_id` (`boleto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `categorias_chamados`
--
ALTER TABLE `categorias_chamados`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `categorias_financeiras`
--
ALTER TABLE `categorias_financeiras`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `chamados`
--
ALTER TABLE `chamados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `solicitante_id` (`solicitante_id`),
  ADD KEY `responsavel_id` (`responsavel_id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_departamento` (`departamento`);

--
-- Índices de tabela `chamados_alunos`
--
ALTER TABLE `chamados_alunos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_chamado_aluno` (`chamado_id`,`aluno_id`),
  ADD KEY `idx_chamado` (`chamado_id`),
  ADD KEY `idx_aluno` (`aluno_id`);

--
-- Índices de tabela `chamados_anexos`
--
ALTER TABLE `chamados_anexos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chamado_id` (`chamado_id`),
  ADD KEY `resposta_id` (`resposta_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `chamados_documentos`
--
ALTER TABLE `chamados_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chamado_id` (`chamado_id`),
  ADD KEY `tipo_documento_id` (`tipo_documento_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `polo_id` (`polo_id`);

--
-- Índices de tabela `chamados_historico`
--
ALTER TABLE `chamados_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chamado` (`chamado_id`),
  ADD KEY `idx_usuario` (`usuario_id`);

--
-- Índices de tabela `chamados_respostas`
--
ALTER TABLE `chamados_respostas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chamado_id` (`chamado_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `cidades`
--
ALTER TABLE `cidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estado_id` (`estado_id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `cobranca_polos`
--
ALTER TABLE `cobranca_polos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave_unique` (`chave`);

--
-- Índices de tabela `configuracoes_documentos`
--
ALTER TABLE `configuracoes_documentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `configuracoes_sistema`
--
ALTER TABLE `configuracoes_sistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `contas_bancarias`
--
ALTER TABLE `contas_bancarias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contas_pagar`
--
ALTER TABLE `contas_pagar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `fornecedor_id` (`fornecedor_id`),
  ADD KEY `transacao_id` (`transacao_id`);

--
-- Índices de tabela `contas_pagar_rh`
--
ALTER TABLE `contas_pagar_rh`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pagamento_id` (`pagamento_id`),
  ADD KEY `conta_pagar_id` (`conta_pagar_id`);

--
-- Índices de tabela `contas_receber`
--
ALTER TABLE `contas_receber`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `transacao_id` (`transacao_id`);

--
-- Índices de tabela `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `area_conhecimento_id` (`area_conhecimento_id`),
  ADD KEY `grupo_id` (`grupo_id`),
  ADD KEY `coordenador_id` (`coordenador_id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `professor_padrao_id` (`professor_padrao_id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_documento_id` (`tipo_documento_id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `documentos_alunos`
--
ALTER TABLE `documentos_alunos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `tipo_documento_id` (`tipo_documento_id`),
  ADD KEY `idx_documentos_alunos_aluno_id` (`aluno_id`),
  ADD KEY `idx_documentos_alunos_tipo` (`tipo`);

--
-- Índices de tabela `documentos_emitidos`
--
ALTER TABLE `documentos_emitidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_documento` (`numero_documento`),
  ADD KEY `solicitacao_id` (`solicitacao_id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `emails_enviados`
--
ALTER TABLE `emails_enviados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitacao_id` (`solicitacao_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `estados`
--
ALTER TABLE `estados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sigla` (`sigla`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `folha_pagamento`
--
ALTER TABLE `folha_pagamento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mes_ano` (`mes_referencia`,`ano_referencia`);

--
-- Índices de tabela `folha_pagamento_itens`
--
ALTER TABLE `folha_pagamento_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `folha_id` (`folha_id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `grupos_academicos`
--
ALTER TABLE `grupos_academicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `lancamentos_financeiros`
--
ALTER TABLE `lancamentos_financeiros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `plano_conta_id` (`plano_conta_id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_modulo` (`modulo`),
  ADD KEY `idx_acao` (`acao`),
  ADD KEY `idx_objeto` (`objeto_tipo`,`objeto_id`);

--
-- Índices de tabela `mapeamento_ids_legados`
--
ALTER TABLE `mapeamento_ids_legados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mapeamento` (`tabela`,`id_antigo`,`sistema_origem`),
  ADD KEY `idx_tabela_id_novo` (`tabela`,`id_novo`);

--
-- Índices de tabela `matriculas`
--
ALTER TABLE `matriculas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `turma_id` (`turma_id`),
  ADD KEY `idx_id_legado` (`id_legado`),
  ADD KEY `idx_aluno_curso_polo` (`aluno_id`,`curso_id`,`polo_id`);

--
-- Índices de tabela `mensalidades_alunos`
--
ALTER TABLE `mensalidades_alunos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `aluno_mes` (`aluno_id`,`mes_referencia`);

--
-- Índices de tabela `modulos`
--
ALTER TABLE `modulos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `notas_disciplinas`
--
ALTER TABLE `notas_disciplinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matricula_id` (`matricula_id`),
  ADD KEY `disciplina_id` (`disciplina_id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `funcionario_id` (`funcionario_id`);

--
-- Índices de tabela `permissoes`
--
ALTER TABLE `permissoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_modulo` (`usuario_id`,`modulo`);

--
-- Índices de tabela `plano_contas`
--
ALTER TABLE `plano_contas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `polos`
--
ALTER TABLE `polos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`) USING HASH,
  ADD KEY `cidade_id` (`cidade_id`),
  ADD KEY `responsavel_id` (`responsavel_id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `polos_financeiro`
--
ALTER TABLE `polos_financeiro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `polo_tipo_unique` (`polo_id`,`tipo_polo_id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `tipo_polo_id` (`tipo_polo_id`);

--
-- Índices de tabela `polos_financeiro_historico`
--
ALTER TABLE `polos_financeiro_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `tipo_polo_id` (`tipo_polo_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `polos_tipos`
--
ALTER TABLE `polos_tipos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `tipo_polo_id` (`tipo_polo_id`);

--
-- Índices de tabela `polos_tipos_backup`
--
ALTER TABLE `polos_tipos_backup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `polo_tipo_unique` (`polo_id`,`tipo_polo_id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `tipo_polo_id` (`tipo_polo_id`);

--
-- Índices de tabela `professores`
--
ALTER TABLE `professores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

--
-- Índices de tabela `solicitacoes_documentos`
--
ALTER TABLE `solicitacoes_documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `tipo_documento_id` (`tipo_documento_id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `solicitacoes_s`
--
ALTER TABLE `solicitacoes_s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `protocolo` (`protocolo`);

--
-- Índices de tabela `tipos_documentos`
--
ALTER TABLE `tipos_documentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tipos_documentos_pessoais`
--
ALTER TABLE `tipos_documentos_pessoais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tipos_polos`
--
ALTER TABLE `tipos_polos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tipos_polos_financeiro`
--
ALTER TABLE `tipos_polos_financeiro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo_polo_id` (`tipo_polo_id`);

--
-- Índices de tabela `transacoes`
--
ALTER TABLE `transacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `conta_id` (`conta_id`);

--
-- Índices de tabela `transacoes_financeiras`
--
ALTER TABLE `transacoes_financeiras`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `turmas`
--
ALTER TABLE `turmas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `polo_id` (`polo_id`),
  ADD KEY `professor_coordenador_id` (`professor_coordenador_id`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `idx_id_legado` (`id_legado`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendamentos_pagamentos`
--
ALTER TABLE `agendamentos_pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `alunos`
--
ALTER TABLE `alunos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `alunos_acesso`
--
ALTER TABLE `alunos_acesso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `alunos_atividades`
--
ALTER TABLE `alunos_atividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `alunos_preferencias`
--
ALTER TABLE `alunos_preferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `alunos_sessoes`
--
ALTER TABLE `alunos_sessoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `areas_conhecimento`
--
ALTER TABLE `areas_conhecimento`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_acessos`
--
ALTER TABLE `ava_acessos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_aulas`
--
ALTER TABLE `ava_aulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_categorias`
--
ALTER TABLE `ava_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_certificados`
--
ALTER TABLE `ava_certificados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_cursos`
--
ALTER TABLE `ava_cursos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_imagens`
--
ALTER TABLE `ava_imagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_materiais`
--
ALTER TABLE `ava_materiais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_matriculas`
--
ALTER TABLE `ava_matriculas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_modulos`
--
ALTER TABLE `ava_modulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_polos_acesso`
--
ALTER TABLE `ava_polos_acesso`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_progresso`
--
ALTER TABLE `ava_progresso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_progresso_aulas`
--
ALTER TABLE `ava_progresso_aulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_questoes`
--
ALTER TABLE `ava_questoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ava_respostas_alunos`
--
ALTER TABLE `ava_respostas_alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `boletos`
--
ALTER TABLE `boletos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `boletos_historico`
--
ALTER TABLE `boletos_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias_chamados`
--
ALTER TABLE `categorias_chamados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias_financeiras`
--
ALTER TABLE `categorias_financeiras`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chamados`
--
ALTER TABLE `chamados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chamados_alunos`
--
ALTER TABLE `chamados_alunos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chamados_anexos`
--
ALTER TABLE `chamados_anexos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chamados_documentos`
--
ALTER TABLE `chamados_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chamados_historico`
--
ALTER TABLE `chamados_historico`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chamados_respostas`
--
ALTER TABLE `chamados_respostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cidades`
--
ALTER TABLE `cidades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cobranca_polos`
--
ALTER TABLE `cobranca_polos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracoes_documentos`
--
ALTER TABLE `configuracoes_documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracoes_sistema`
--
ALTER TABLE `configuracoes_sistema`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contas_bancarias`
--
ALTER TABLE `contas_bancarias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contas_pagar`
--
ALTER TABLE `contas_pagar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contas_pagar_rh`
--
ALTER TABLE `contas_pagar_rh`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contas_receber`
--
ALTER TABLE `contas_receber`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `disciplinas`
--
ALTER TABLE `disciplinas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `documentos_alunos`
--
ALTER TABLE `documentos_alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `documentos_emitidos`
--
ALTER TABLE `documentos_emitidos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `emails_enviados`
--
ALTER TABLE `emails_enviados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estados`
--
ALTER TABLE `estados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `folha_pagamento`
--
ALTER TABLE `folha_pagamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `folha_pagamento_itens`
--
ALTER TABLE `folha_pagamento_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `grupos_academicos`
--
ALTER TABLE `grupos_academicos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `lancamentos_financeiros`
--
ALTER TABLE `lancamentos_financeiros`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `mapeamento_ids_legados`
--
ALTER TABLE `mapeamento_ids_legados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `matriculas`
--
ALTER TABLE `matriculas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `mensalidades_alunos`
--
ALTER TABLE `mensalidades_alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `modulos`
--
ALTER TABLE `modulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notas_disciplinas`
--
ALTER TABLE `notas_disciplinas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `permissoes`
--
ALTER TABLE `permissoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `plano_contas`
--
ALTER TABLE `plano_contas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `polos`
--
ALTER TABLE `polos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `polos_financeiro`
--
ALTER TABLE `polos_financeiro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `polos_financeiro_historico`
--
ALTER TABLE `polos_financeiro_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `polos_tipos`
--
ALTER TABLE `polos_tipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `polos_tipos_backup`
--
ALTER TABLE `polos_tipos_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `professores`
--
ALTER TABLE `professores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `solicitacoes_documentos`
--
ALTER TABLE `solicitacoes_documentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `solicitacoes_s`
--
ALTER TABLE `solicitacoes_s`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tipos_documentos`
--
ALTER TABLE `tipos_documentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tipos_documentos_pessoais`
--
ALTER TABLE `tipos_documentos_pessoais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tipos_polos`
--
ALTER TABLE `tipos_polos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tipos_polos_financeiro`
--
ALTER TABLE `tipos_polos_financeiro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `transacoes`
--
ALTER TABLE `transacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `transacoes_financeiras`
--
ALTER TABLE `transacoes_financeiras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agendamentos_pagamentos`
--
ALTER TABLE `agendamentos_pagamentos`
  ADD CONSTRAINT `agendamentos_pagamentos_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `alunos`
--
ALTER TABLE `alunos`
  ADD CONSTRAINT `alunos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alunos_ibfk_2` FOREIGN KEY (`cidade_id`) REFERENCES `cidades` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alunos_ibfk_3` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alunos_ibfk_4` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alunos_ibfk_5` FOREIGN KEY (`professor_orientador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `ava_aulas`
--
ALTER TABLE `ava_aulas`
  ADD CONSTRAINT `fk_ava_aulas_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `ava_modulos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ava_cursos`
--
ALTER TABLE `ava_cursos`
  ADD CONSTRAINT `fk_ava_cursos_polo` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ava_materiais`
--
ALTER TABLE `ava_materiais`
  ADD CONSTRAINT `fk_ava_materiais_aula` FOREIGN KEY (`aula_id`) REFERENCES `ava_aulas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ava_modulos`
--
ALTER TABLE `ava_modulos`
  ADD CONSTRAINT `fk_ava_modulos_curso` FOREIGN KEY (`curso_id`) REFERENCES `ava_cursos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ava_polos_acesso`
--
ALTER TABLE `ava_polos_acesso`
  ADD CONSTRAINT `fk_ava_polos_acesso_polo` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ava_polos_acesso_usuario` FOREIGN KEY (`liberado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `ava_questoes`
--
ALTER TABLE `ava_questoes`
  ADD CONSTRAINT `fk_ava_questoes_aula` FOREIGN KEY (`aula_id`) REFERENCES `ava_aulas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `chamados`
--
ALTER TABLE `chamados`
  ADD CONSTRAINT `fk_chamados_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_chamados_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_chamados` (`id`),
  ADD CONSTRAINT `fk_chamados_polo` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_chamados_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_chamados_solicitante` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `chamados_anexos`
--
ALTER TABLE `chamados_anexos`
  ADD CONSTRAINT `fk_anexos_chamado` FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_anexos_resposta` FOREIGN KEY (`resposta_id`) REFERENCES `chamados_respostas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_anexos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `chamados_documentos`
--
ALTER TABLE `chamados_documentos`
  ADD CONSTRAINT `fk_chamados_documentos_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`),
  ADD CONSTRAINT `fk_chamados_documentos_chamado` FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chamados_documentos_polo` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`),
  ADD CONSTRAINT `fk_chamados_documentos_tipo` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documentos` (`id`);

--
-- Restrições para tabelas `chamados_respostas`
--
ALTER TABLE `chamados_respostas`
  ADD CONSTRAINT `fk_respostas_chamado` FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_respostas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `cidades`
--
ALTER TABLE `cidades`
  ADD CONSTRAINT `cidades_ibfk_1` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `contas_pagar_rh`
--
ALTER TABLE `contas_pagar_rh`
  ADD CONSTRAINT `contas_pagar_rh_ibfk_1` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contas_pagar_rh_ibfk_2` FOREIGN KEY (`conta_pagar_id`) REFERENCES `contas_pagar` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`area_conhecimento_id`) REFERENCES `areas_conhecimento` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cursos_ibfk_2` FOREIGN KEY (`grupo_id`) REFERENCES `grupos_academicos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cursos_ibfk_3` FOREIGN KEY (`coordenador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `documentos_ibfk_1` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documentos_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `documentos_emitidos`
--
ALTER TABLE `documentos_emitidos`
  ADD CONSTRAINT `documentos_emitidos_ibfk_1` FOREIGN KEY (`solicitacao_id`) REFERENCES `solicitacoes_documentos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `folha_pagamento_itens`
--
ALTER TABLE `folha_pagamento_itens`
  ADD CONSTRAINT `folha_pagamento_itens_ibfk_1` FOREIGN KEY (`folha_id`) REFERENCES `folha_pagamento` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folha_pagamento_itens_ibfk_2` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `lancamentos_financeiros`
--
ALTER TABLE `lancamentos_financeiros`
  ADD CONSTRAINT `lancamentos_financeiros_ibfk_1` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lancamentos_financeiros_ibfk_2` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lancamentos_financeiros_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lancamentos_financeiros_ibfk_4` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_financeiras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lancamentos_financeiros_ibfk_5` FOREIGN KEY (`plano_conta_id`) REFERENCES `plano_contas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `matriculas`
--
ALTER TABLE `matriculas`
  ADD CONSTRAINT `matriculas_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matriculas_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matriculas_ibfk_3` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `matriculas_ibfk_4` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `notas_disciplinas`
--
ALTER TABLE `notas_disciplinas`
  ADD CONSTRAINT `notas_disciplinas_ibfk_1` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notas_disciplinas_ibfk_2` FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `permissoes`
--
ALTER TABLE `permissoes`
  ADD CONSTRAINT `permissoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `polos`
--
ALTER TABLE `polos`
  ADD CONSTRAINT `polos_ibfk_1` FOREIGN KEY (`cidade_id`) REFERENCES `cidades` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `polos_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `solicitacoes_documentos`
--
ALTER TABLE `solicitacoes_documentos`
  ADD CONSTRAINT `solicitacoes_documentos_ibfk_2` FOREIGN KEY (`polo_id`) REFERENCES `polos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitacoes_documentos_ibfk_3` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documentos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tipos_polos_financeiro`
--
ALTER TABLE `tipos_polos_financeiro`
  ADD CONSTRAINT `tipos_polos_financeiro_ibfk_1` FOREIGN KEY (`tipo_polo_id`) REFERENCES `tipos_polos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `turmas`
--
ALTER TABLE `turmas`
  ADD CONSTRAINT `turmas_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `turmas_ibfk_3` FOREIGN KEY (`professor_coordenador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
