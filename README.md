# DW User Management for WooCommerce

Este plugin para WordPress/WooCommerce oferece uma solução completa para gerenciar nomes de usuário e exibição, garantindo o formato `Nome.Sobrenome` para novos cadastros e permitindo a atualização em massa para usuários existentes.

## Funcionalidades

- **Novos Cadastros:** O plugin não interfere no processo inicial de cadastro (pop-up de e-mail/senha). Após o usuário finalizar a compra e preencher os dados de faturamento (Nome e Sobrenome), o plugin reescreve automaticamente o nome de usuário para o formato `Nome.Sobrenome` e o nome de exibição para `Nome Sobrenome`.
- **Atualização de Usuários Existentes:** Permite que você atualize os nomes de usuário e nomes de exibição de usuários já existentes para o formato desejado, processando em lotes para evitar sobrecarga do servidor.

## Instalação

1. Faça o download do arquivo `woocommerce-unified-user.zip` (será fornecido ao final).
2. No seu painel administrativo do WordPress, navegue até `Plugins > Adicionar Novo`.
3. Clique em `Fazer upload do plugin`.
4. Escolha o arquivo `woocommerce-unified-user.zip` e clique em `Instalar agora`.
5. Após a instalação, clique em `Ativar Plugin`.

## Como Usar (Atualização de Usuários Existentes)

1. Após ativar o plugin, navegue até `Ferramentas > Gerenciar Usuários DW` no seu painel administrativo do WordPress.
2. Leia as informações e avisos na página.
3. Clique no botão "Iniciar Atualização de Usuários Existentes" para começar o processo.
4. O plugin processará os usuários em lotes de 20 para evitar sobrecarga do servidor. O progresso será exibido na tela.

## Observações Importantes

- **Backup:** É altamente recomendável fazer um backup completo do seu site (arquivos e banco de dados) antes de executar a funcionalidade de atualização em massa, pois ela fará alterações diretas nos dados dos usuários.
- **Recursos do Servidor:** A execução da atualização em massa pode consumir recursos do servidor, especialmente em sites com um grande número de usuários. Se você notar lentidão ou erros, pode ser necessário aumentar o tempo limite de execução do PHP ou a memória disponível no seu servidor, ou entrar em contato com seu provedor de hospedagem.
- **Nome de Usuário Existente:** Se o nome de usuário gerado (`Nome.Sobrenome`) já existir, o plugin tentará adicionar um número ao final (ex: `joao.silva1`, `joao.silva2`, etc.) para garantir a unicidade.
- **Nome e Sobrenome:** As funcionalidades dependem dos campos `first_name` e `last_name` (Nome e Sobrenome) preenchidos no perfil do usuário (para usuários existentes) ou nos dados de faturamento (para novos cadastros). Certifique-se de que esses campos estejam corretos para que o nome de usuário e o nome de exibição sejam gerados adequadamente.
