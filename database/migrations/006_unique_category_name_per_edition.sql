-- Apply after auditing and reconciling any existing duplicate category names.
ALTER TABLE categorias
  ADD UNIQUE KEY uk_categorias_edicao_nome (interclasses_id_interclasse, nome_categoria);
