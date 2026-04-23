<?php
// O Hash que você pegou no banco de dados (exemplo)
$hash_do_banco = '$2y$10$Iue2PRxKXtX1nvOFPZI2DuGt3TdmE2vecUIyeWBbp.RYMm6jzjycK';

// A senha que você ACHA que é a correta (texto puro)
$senha_para_testar = '21092008'; 

if (password_verify($senha_para_testar, $hash_do_banco)) {
    echo "A senha está CORRETA!";
} else {
    echo "Senha INCORRETA ou o Hash não pertence a essa senha.";
}
?>