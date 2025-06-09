<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Configurações de Documentos</h4>
                    <a href="documentos.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Voltar
                    </a>
                </div>
                <div class="card-body">
                    <!-- Abas de configuração -->
                    <ul class="nav nav-tabs" id="configTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tipos-tab" data-toggle="tab" href="#tipos" role="tab" 
                               aria-controls="tipos" aria-selected="true">Tipos de Documentos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="modelos-tab" data-toggle="tab" href="#modelos" role="tab" 
                               aria-controls="modelos" aria-selected="false">Modelos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="assinaturas-tab" data-toggle="tab" href="#assinaturas" role="tab" 
                               aria-controls="assinaturas" aria-selected="false">Assinaturas</a>
                        </li>
                    </ul>
                    
                    <div class="tab-content p-3 border border-top-0 rounded-bottom" id="configTabsContent">
                        <!-- Tipos de Documentos -->
                        <div class="tab-pane fade show active" id="tipos" role="tabpanel" aria-labelledby="tipos-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Tipos de Documentos</h5>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovoTipo">
                                    <i class="fas fa-plus mr-1"></i> Novo Tipo
                                </button>
                            </div>
                            
                            <?php
                            // Busca os tipos de documentos
                            $sql = "SELECT * FROM tipos_documentos ORDER BY nome";
                            $tipos = executarConsultaAll($db, $sql);
                            ?>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>Descrição</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tipos as $tipo): ?>
                                        <tr>
                                            <td><?php echo $tipo['id']; ?></td>
                                            <td><?php echo htmlspecialchars($tipo['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($tipo['descricao']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $tipo['status'] == 'ativo' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($tipo['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info editar-tipo" 
                                                        data-id="<?php echo $tipo['id']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($tipo['nome']); ?>"
                                                        data-descricao="<?php echo htmlspecialchars($tipo['descricao']); ?>"
                                                        data-status="<?php echo $tipo['status']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger excluir-tipo" 
                                                        data-id="<?php echo $tipo['id']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($tipo['nome']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Modelos de Documentos -->
                        <div class="tab-pane fade" id="modelos" role="tabpanel" aria-labelledby="modelos-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Modelos de Documentos</h5>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovoModelo">
                                    <i class="fas fa-plus mr-1"></i> Novo Modelo
                                </button>
                            </div>
                            
                            <?php
                            // Busca os modelos de documentos
                            $sql = "SELECT m.*, td.nome as tipo_nome 
                                    FROM modelos_documentos m
                                    LEFT JOIN tipos_documentos td ON m.tipo_documento_id = td.id
                                    ORDER BY td.nome, m.nome";
                            $modelos = executarConsultaAll($db, $sql);
                            ?>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Tipo de Documento</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modelos as $modelo): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($modelo['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($modelo['tipo_nome']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $modelo['status'] == 'ativo' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($modelo['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info editar-modelo" 
                                                        data-id="<?php echo $modelo['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger excluir-modelo" 
                                                        data-id="<?php echo $modelo['id']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($modelo['nome']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Assinaturas -->
                        <div class="tab-pane fade" id="assinaturas" role="tabpanel" aria-labelledby="assinaturas-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Assinaturas</h5>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovaAssinatura">
                                    <i class="fas fa-plus mr-1"></i> Nova Assinatura
                                </button>
                            </div>
                            
                            <?php
                            // Busca as assinaturas
                            $sql = "SELECT * FROM assinaturas ORDER BY nome";
                            $assinaturas = executarConsultaAll($db, $sql);
                            ?>
                            
                            <div class="row">
                                <?php foreach ($assinaturas as $assinatura): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h5 class="card-title"><?php echo htmlspecialchars($assinatura['nome']); ?></h5>
                                            <p class="card-text"><?php echo htmlspecialchars($assinatura['cargo']); ?></p>
                                            <?php if (!empty($assinatura['imagem'])): ?>
                                            <img src="uploads/assinaturas/<?php echo $assinatura['imagem']; ?>" 
                                                 alt="Assinatura" class="img-fluid mb-3" style="max-height: 100px;">
                                            <?php endif; ?>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info editar-assinatura" 
                                                        data-id="<?php echo $assinatura['id']; ?>">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger excluir-assinatura" 
                                                        data-id="<?php echo $assinatura['id']; ?>"
                                                        data-nome="<?php echo htmlspecialchars($assinatura['nome']); ?>">
                                                    <i class="fas fa-trash"></i> Excluir
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Tipo de Documento -->
<div class="modal fade" id="modalNovoTipo" tabindex="-1" role="dialog" aria-labelledby="modalNovoTipoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="documentos.php?action=salvar_tipo" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovoTipoLabel">Novo Tipo de Documento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Outros modais (edição, exclusão, etc.) seriam adicionados aqui -->