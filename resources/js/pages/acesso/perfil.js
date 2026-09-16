window.SGIPage.mount("acesso/perfil", function (pageConfig, pageScope) {

    var API_BASE = (window.SGI_API_BASE || '/api/v1/').replace(/\/?$/, '/');
    const DADOS_PERFIL = {
        nome: pageConfig.value2,
        matricula: pageConfig.value3,
        id: pageConfig.value4,
        nivel: pageConfig.value5
    };
    const API_FOTO = API_BASE + 'foto';
    const esc = (value) => window.SGIHtml
        ? window.SGIHtml.escape(value)
        : String(value == null ? '' : value).replace(/[&<>"']/g, (character) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character]));

    let fotoPreviewFile = null;
    let temFotoAtual = false;

    function esconderSkeleton(suf) {
        const skel = document.getElementById('fotoSkeleton' + suf);
        if (skel) skel.classList.add('d-none');
    }

    function mostrarFallbackFoto(suf) {
        const img = document.getElementById('fotoImg' + suf);
        const icon = document.getElementById('fotoIcon' + suf);
        if (img) img.classList.add('d-none');
        if (icon) icon.classList.remove('d-none');
        esconderSkeleton(suf);
    }

    function preencherPerfil() {
        document.getElementById('perfilNomeMob').textContent = DADOS_PERFIL.nome;
        document.getElementById('perfilMatriculaMob').textContent = DADOS_PERFIL.matricula;
        document.getElementById('perfilNomeDesk').textContent = DADOS_PERFIL.nome;
        document.getElementById('perfilMatriculaDesk').textContent = DADOS_PERFIL.matricula;
        const nomeInfo = document.getElementById('perfilNomeInfo');
        if (nomeInfo) nomeInfo.textContent = DADOS_PERFIL.nome;
        const editarNome = document.getElementById('editarNome');
        if (editarNome) editarNome.value = DADOS_PERFIL.nome;
    }

    function mostrarFoto(url) {
        if (!url) return;
        ['Mob', 'Desk'].forEach(suf => {
            const img = document.getElementById('fotoImg' + suf);
            const icon = document.getElementById('fotoIcon' + suf);
            if (img && icon) {
                img.onload = () => {
                    img.classList.remove('d-none');
                    icon.classList.add('d-none');
                    esconderSkeleton(suf);
                };
                img.onerror = () => {
                    mostrarFallbackFoto(suf);
                };
                img.src = url;
            }
        });
    }

    function atualizarBotoesFoto() {
        const temPreview = fotoPreviewFile !== null;
        ['Mob', 'Desk'].forEach(suf => {
            const btnSalvar = document.getElementById('btnSalvarFoto' + suf);
            const btnExcluir = document.getElementById('btnExcluirFoto' + suf);
            if (btnSalvar) btnSalvar.classList.toggle('d-none', !temPreview);
            if (btnExcluir) btnExcluir.disabled = !temFotoAtual;
        });
    }

    function mostrarToast(mensagem, tipo) {
        window.SGI.showToast(mensagem, tipo);
    }

    function toggleCampoSenha(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const mostrar = input.type === 'password';
        input.type = mostrar ? 'text' : 'password';
        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-eye', mostrar);
            icon.classList.toggle('bi-eye-slash', !mostrar);
        }
        btn.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
        btn.setAttribute('aria-pressed', String(mostrar));
    }

    window.SGIPage.ready( async () => {
        document.querySelectorAll('.perfil-password-eye').forEach(btn => {
            pageScope.listen(btn, 'click', () => toggleCampoSenha(btn.dataset.target, btn));
        });

        try {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            const nome = ativo?.nome_interclasse || 'Interclasse';
            document.getElementById('perfilNomeInterMobile').textContent = nome;
            document.getElementById('perfilNomeInterDesk').textContent = nome;
        } catch (e) {}

        const params = new URLSearchParams(window.location.search);
        const id = params.get('id');
        if (id) {
            const basePath = String(window.SGI_BASE_PATH || '').replace(/\/+$/, '');
            const href = basePath + '/painel?id=' + encodeURIComponent(id);
            document.getElementById('perfilBackDesk').href = href;
            const mob = document.getElementById('perfilBackMob');
            if (mob) mob.href = href;
        }

        preencherPerfil();

        const input = document.getElementById('fotoUploadInput');
        ['btnCameraMob', 'btnCameraDesk'].forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn && input) pageScope.listen(btn, 'click', () => input.click());
        });

        (async () => {
            try {
                const resp = await fetch(API_FOTO + '?user_id=' + DADOS_PERFIL.id);
                const data = await resp.json();
                if (data.success && data.foto_usuario) {
                    temFotoAtual = true;
                    var assetBase = (window.SGI_BASE_PATH || '') + '/uploads/fotosUsuarios/';
                    mostrarFoto(assetBase.replace(/\/+/g, '/') + encodeURIComponent(data.foto_usuario));
                    atualizarBotoesFoto();
                } else {
                    ['Mob', 'Desk'].forEach(mostrarFallbackFoto);
                }
            } catch (e) {
                ['Mob', 'Desk'].forEach(mostrarFallbackFoto);
            }
        })();

        if (input) {
            pageScope.listen(input, 'change', () => {
                const file = input.files?.[0];
                if (!file) return;
                const url = URL.createObjectURL(file);
                mostrarFoto(url);
                fotoPreviewFile = file;
                atualizarBotoesFoto();
                input.value = '';
            });
        }

        document.querySelectorAll('[id^="btnSalvarFoto"]').forEach(btn => {
            pageScope.listen(btn, 'click', async () => {
                if (!fotoPreviewFile) return;
                const fd = new FormData();
                fd.append('foto', fotoPreviewFile);
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';
                try {
                    const resp = await fetch(API_FOTO, { method: 'POST', body: fd });
                    const data = await resp.json().catch(() => null);
                    if (resp.ok && data?.success === true && data.arquivo) {
                        mostrarToast('Foto atualizada com sucesso!', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        mostrarToast(data?.mensagem || 'Resposta inválida ao enviar foto.', 'error');
                    }
                } catch (e) {
                    mostrarToast('Erro de conexão.', 'error');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
                }
            });
        });

        document.querySelectorAll('[id^="btnExcluirFoto"]').forEach(btn => {
            pageScope.listen(btn, 'click', async () => {
                if (!await SGI.confirm({ titulo: 'Remover foto de perfil?', mensagem: 'A foto atual será removida do seu perfil.', textoConfirmar: 'Remover foto', destrutivo: true })) return;
                try {
                    const resp = await fetch(API_FOTO, { method: 'DELETE' });
                    const data = await resp.json();
                    if (resp.ok && data.success === true && (data.offline === true || data.queued === true)) {
                        mostrarToast('Remoção pendente: a foto será removida quando a conexão voltar.', 'info');
                        return;
                    }
                    if (resp.ok && data.success === true) {
                        mostrarToast('Foto removida.', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        mostrarToast(data.mensagem || 'Erro ao remover foto.', 'error');
                    }
                } catch (e) {
                    mostrarToast('Erro de conexão.', 'error');
                }
            });
        });

        pageScope.listen(document.getElementById('formEditarPerfil'), 'submit', salvarPerfil);
        pageScope.listen(document.getElementById('formAlterarSenha'), 'submit', salvarSenha);
    });

    async function salvarPerfil(e) {
        e.preventDefault();
        const msgEl = document.getElementById('msgEditarPerfil');
        const btn = document.getElementById('btnSalvarPerfil');
        msgEl.innerHTML = '';
        const nome = document.getElementById('editarNome').value.trim();
        if (!nome) {
            msgEl.innerHTML = '<span class="text-danger">O nome não pode ficar vazio.</span>';
            return;
        }
        const fd = new FormData();
        fd.append('salvar_perfil', '1');
        fd.append('nome_usuario', nome);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';
        try {
            const resp = await fetch(window.location.href, { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) {
                mostrarToast(data.message || 'Perfil atualizado!', 'success');
                DADOS_PERFIL.nome = nome;
                preencherPerfil();
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarPerfil'))?.hide();
                    msgEl.innerHTML = '';
                }, 800);
            } else {
                msgEl.innerHTML = '<span class="text-danger">' + esc(data.message || 'Erro ao salvar.') + '</span>';
            }
        } catch (err) {
            msgEl.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
        }
    }

    async function salvarSenha(e) {
        e.preventDefault();
        const msgEl = document.getElementById('msgAlterarSenha');
        const btn = document.getElementById('btnSalvarSenha');
        msgEl.innerHTML = '';
        const senhaAtual = document.getElementById('editarSenhaAtual').value;
        const novaSenha = document.getElementById('editarNovaSenha').value;
        const confirmarSenha = document.getElementById('editarConfirmarSenha').value;
        if (!senhaAtual || !novaSenha || !confirmarSenha) {
            msgEl.innerHTML = '<span class="text-danger">Preencha todos os campos.</span>';
            return;
        }
        if (novaSenha.length < 6) {
            msgEl.innerHTML = '<span class="text-danger">A nova senha deve ter no mínimo 6 caracteres.</span>';
            return;
        }
        if (novaSenha !== confirmarSenha) {
            msgEl.innerHTML = '<span class="text-danger">As senhas não coincidem.</span>';
            return;
        }
        const fd = new FormData();
        fd.append('salvar_perfil', '1');
        fd.append('nome_usuario', DADOS_PERFIL.nome);
        fd.append('senha_atual', senhaAtual);
        fd.append('nova_senha', novaSenha);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';
        try {
            const resp = await fetch(window.location.href, { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) {
                mostrarToast('Senha alterada com sucesso!', 'success');
                document.getElementById('editarSenhaAtual').value = '';
                document.getElementById('editarNovaSenha').value = '';
                document.getElementById('editarConfirmarSenha').value = '';
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalAlterarSenha'))?.hide();
                    msgEl.innerHTML = '';
                }, 800);
            } else {
                msgEl.innerHTML = '<span class="text-danger">' + esc(data.message || 'Erro ao alterar senha.') + '</span>';
            }
        } catch (err) {
            msgEl.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
        }
    }

return {esconderSkeleton, preencherPerfil, mostrarFoto, atualizarBotoesFoto, mostrarToast, toggleCampoSenha, salvarPerfil, salvarSenha};
});
