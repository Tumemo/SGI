window.SGIPage.mount("aluno/perfil", function (pageConfig, pageScope) {

    const APP_BASE = window.SGI_BASE_PATH || '';

    const DADOS_PERFIL = {
        nome: pageConfig.value2,
        matricula: pageConfig.value3,
        id: pageConfig.value4,
        nivel: pageConfig.value5
    };
    const API_BASE = (window.SGI_API_BASE || '/api/v1/').replace(/\/?$/, '/');
    const API_FOTO = API_BASE + 'foto';

    let fotoPreviewFile = null;
    let temFotoAtual = false;

    function esconderSkeleton(suf) {
        const skel = document.getElementById('fotoSkeleton' + suf);
        if (skel) skel.classList.add('d-none');
    }

    function preencherPerfil() {
        document.getElementById('perfilNomeMob').textContent = DADOS_PERFIL.nome;
        document.getElementById('perfilEmailMob').textContent = DADOS_PERFIL.matricula;
        document.getElementById('perfilNomeDesk').textContent = DADOS_PERFIL.nome;
        document.getElementById('perfilEmailDesk').textContent = DADOS_PERFIL.matricula;
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
                    img.classList.add('d-none');
                    icon.classList.remove('d-none');
                    esconderSkeleton(suf);
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
        btn.innerHTML = '<i class="bi bi-' + (mostrar ? 'eye' : 'eye-slash') + '"></i>';
        btn.setAttribute('aria-label', mostrar ? 'Esconder senha' : 'Mostrar senha');
    }

    function toggleSenha(suf) {
        const span = document.getElementById('perfilSenha' + suf);
        const btn = document.getElementById('perfilEye' + suf);
        if (!span || !btn) return;
        if (span.dataset.revealed === 'true') {
            span.textContent = '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022';
            span.dataset.revealed = 'false';
            btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
            btn.setAttribute('aria-label', 'Mostrar senha');
        } else {
            span.textContent = '********';
            span.dataset.revealed = 'true';
            btn.innerHTML = '<i class="bi bi-eye"></i>';
            btn.setAttribute('aria-label', 'Esconder senha');
        }
    }

    window.SGIPage.ready( async () => {
        preencherPerfil();

        document.querySelectorAll('.perfil-password-eye').forEach(btn => {
            pageScope.listen(btn, 'click', () => toggleCampoSenha(btn.dataset.target, btn));
        });

        pageScope.listen(document.getElementById('perfilEyeMob'), 'click', () => toggleSenha('Mob'));
        pageScope.listen(document.getElementById('perfilEyeDesk'), 'click', () => toggleSenha('Desk'));

        const input = document.getElementById('fotoUploadInput');
        ['btnCameraMob', 'btnCameraDesk'].forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn) pageScope.listen(btn, 'click', () => input.click());
        });

        (async () => {
            try {
                const resp = await fetch(API_FOTO + '?user_id=' + DADOS_PERFIL.id);
                const data = await resp.json();
                if (data.success && data.foto_usuario) {
                    temFotoAtual = true;
                    mostrarFoto(APP_BASE + '/uploads/fotosUsuarios/' + encodeURIComponent(data.foto_usuario));
                    atualizarBotoesFoto();
                } else {
                    ['Mob', 'Desk'].forEach(esconderSkeleton);
                }
            } catch (e) {
                ['Mob', 'Desk'].forEach(esconderSkeleton);
            }
        })();

        pageScope.listen(input, 'change', () => {
            const file = input.files?.[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            mostrarFoto(url);
            fotoPreviewFile = file;
            atualizarBotoesFoto();
            input.value = '';
        });

        document.querySelectorAll('[id^="btnSalvarFoto"]').forEach(btn => {
            pageScope.listen(btn, 'click', async () => {
                if (!fotoPreviewFile) return;
                const fd = new FormData();
                fd.append('foto', fotoPreviewFile);
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';
                try {
                    const resp = await fetch(API_FOTO, { method: 'POST', body: fd });
                    const data = await resp.json();
                    if (data.success && data.arquivo) {
                        mostrarToast('Foto atualizada com sucesso!', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        mostrarToast(data.mensagem || 'Erro ao enviar foto.', 'error');
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
                if (!confirm('Remover foto de perfil?')) return;
                try {
                    const fd = new FormData();
                    fd.append('acao', 'remover_foto');
                    const resp = await fetch(window.location.href, { method: 'POST', body: fd });
                    const data = await resp.json();
                    if (data.success) {
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
                msgEl.innerHTML = '<span class="text-danger">' + (data.message || 'Erro ao salvar.') + '</span>';
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
                msgEl.innerHTML = '<span class="text-danger">' + (data.message || 'Erro ao alterar senha.') + '</span>';
            }
        } catch (err) {
            msgEl.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
        }
    }

return {esconderSkeleton, preencherPerfil, mostrarFoto, atualizarBotoesFoto, mostrarToast, toggleCampoSenha, toggleSenha, salvarPerfil, salvarSenha};
});
