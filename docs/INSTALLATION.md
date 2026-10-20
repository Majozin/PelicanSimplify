# Instalação

Alvo: Pelican 1.0.0-beta38 ou 1.0.0-rc.1, com PHP 8.3 ou mais novo. Esta é uma versão beta.

## Importação pelo painel

Em **Admin → Plugins → Importar de URL**, cole `https://raw.githubusercontent.com/Majozin/PelicanSimplify/main/dist/pelican-simplify-beta-latest.zip` (a versão mais recente deste fork, em português), importe e clique em **Instalar**. Você também pode enviar o ZIP pela importação. O repositório segue a estrutura padrão de plugins do Pelican, com plugin.json e src na raiz. O instalador verifica as dependências antes de ativar o plugin. Abra as Configurações do Simplify e conclua a configuração depois que todas as verificações obrigatórias passarem.

São necessários: Node.js 22.12+ ou 24+, Yarn, as extensões PHP e as dependências do Composer do painel, o storage do Laravel com permissão de escrita, banco de dados e cache funcionando, e uma conexão de fila persistente. A página de configuração lista as verificações que falharam e como corrigi-las; use **Verificar de novo** depois de corrigir. A verificação da fila configurada não prova que o worker ou o agendador estejam rodando. Mantenha os dois serviços ativos, como o Pelican exige.

O Simplify traz o próprio CSS e JavaScript, sem build separado. Os assets compilados do próprio Pelican precisam existir e ter permissão de leitura. Se a verificação de build falhar, corrija o Node/Yarn e rode `yarn install` e `yarn build` na pasta do Pelican. A entrega no navegador, a configuração de proxy reverso e as operações do Wings ainda precisam ser testadas na sua instalação.

Para receber as atualizações deste fork, ative **"Receber versões beta do Simplify"** em Configurações do Simplify → Gerenciador de plugins.

## Migração a partir do criador avulso

O antigo criador avulso não pode rodar junto com o criador unificado. Use o pacote completo de migração em vez da importação pelo painel:

```bash
sudo bash INSTALL-PELICAN-SIMPLIFY.sh /var/www/pelican core
```

O caminho pode ser alterado. O instalador verifica as dependências como o usuário do painel antes de substituir arquivos, preserva a configuração e cria uma cópia para reversão. Use exatamente o caminho de backup exibido durante a instalação:

```bash
sudo bash ROLLBACK-PELICAN-SIMPLIFY.sh /var/backups/pelican-simplify-XXXXXXXX /var/www/pelican
```

## Desenvolvimento

Rode `bash tests/run.sh` com PHP 8.3 e Node instalados. Para a verificação de tema no navegador, rode `npm ci`, `npx playwright install chromium` e depois `RUN_BROWSER_TESTS=1 bash tests/run.sh`.

## Redefinir os plugins existentes para testar o assistente de configuração

Rode o script de redefinição no servidor do painel:

```bash
sudo bash RESET-PLUGINS.sh reset /var/www/pelican
```

Ele coloca o painel em modo de manutenção, arquiva e verifica todos os plugins atuais, move-os para fora da pasta de plugins ativos, limpa os caches do painel e redefine apenas o indicador de configuração concluída do Simplify. Depois, importe o ZIP do plugin em Admin → Plugins e clique em Instalar. Todos os plugins anteriores do painel, incluindo o Essentials, ficam inativos até serem restaurados ou reinstalados. Servidores de jogo, contas, eggs, Wings e arquivos dos servidores não são tocados.

O script exibe o comando exato de restauração. Guarde a pasta de backup até testar a nova instalação. Se a redefinição falhar, ele tenta restaurar automaticamente a pasta de plugins e o estado de configuração anteriores.
