# Roadmap

Trabalho planejado depois das betas 0.3 do projeto original. Escopo e ordem podem mudar.

*Nota deste fork: este é o roadmap do projeto original, escrito na época das betas 0.3. Alguns itens já foram entregues desde então, como o criador fácil de servidor e o modo host. O roadmap mais recente está na seção "Próximos recursos" do [README](../README.md).*

## Criador fácil de servidor
Um complemento separado, ao lado do **criador fácil de servidor Minecraft**.
- Montar um índice pesquisável de jogos a partir das fontes de eggs do Pelican (o mesmo índice do GitHub usado pelo diálogo *Importar egg* do Pelican: Games Standalone, Games SteamCMD, Minecraft, …), para que o usuário procure "Valheim" em vez de escolher um egg.
- Clicar num jogo baixa e instala automaticamente o egg correspondente para usuários com a permissão *Instalar eggs ausentes* (Usuários e acesso) e depois segue com as mesmas etapas guiadas do criador de Minecraft.
- Manter um cache pequeno do índice e atualizá-lo em segundo plano.

## Reformulação da instalação de servidores
- Mostrar um único estado claro ("Iniciando o egg…", "Iniciando o servidor…") com progresso até o servidor ficar pronto, em vez do liga e desliga durante a instalação.
- Aceitar o EULA do Minecraft antes da primeira inicialização (gravando `eula.txt` durante a instalação), para que o servidor não pare várias vezes antes.

## O Simplify como painel inteiro
- Opção no assistente de configuração: o Simplify substitui o painel. O painel nativo (legado) continua acessível em `/legacy`.
- Opcional: desligar o painel legado por completo. Só é oferecido quando nenhum outro plugin está instalado, porque outros plugins adicionam páginas aos painéis do Pelican.
- Depende de o instalador de plugins do Minecraft e os instaladores de mods estarem prontos antes.

## Modo host
- Hospedagens maiores (vários nodes) podem mudar para o modo host: servidores numa lista compacta por node à esquerda e as páginas de administração do Pelican como área de trabalho principal.

## Experiência no celular
- Barra de abas embaixo (Servidores, Criar, Alertas, Conta) e uma barra superior que se recolhe ao rolar.
- Telas cheias com alça de arrastar em vez de janelas centralizadas; a barra de salvar fica acima da barra de abas.
- Página do servidor no celular: um cabeçalho de status com botões de energia grandes, seções que se trocam deslizando (Console, Jogadores, Arquivos, Configurações) e um console com barra de comando acima do teclado e atalhos de comandos rápidos.
- Páginas embutidas do Pelican (arquivos, administração) reestilizadas para telas estreitas: uma coluna e áreas de toque de 44 px.
- Modo host no celular: um seletor de node e servidor em vez de três colunas.
- App web instalável (manifest, ícones, cor do tema) e suporte a área segura para celulares com notch.
- Verificado com capturas de tela automáticas de celular (360, 390 e 430 px de largura) nos testes de navegador.

## Página do servidor
- Barra de status fixa: nome, status, endereço, jogadores, tempo ligado e os botões de energia sempre à vista.
- Layout por jogo: console primeiro para servidores com mods, jogadores primeiro para servidores movimentados; os usuários podem reordenar ou esconder blocos.
- Áreas de trabalho (arquivos, backups, configurações, jogadores) abrem como abas dentro da página em vez de janelas, com botão de voltar e links diretos.
- Console: filtrar por erros e avisos, pesquisar, pausar a rolagem, e destacar entradas de jogadores e travamentos.
- Um único cartão de "precisa de atenção": reinícios pendentes, backups com falha, software desatualizado, travamento na última inicialização.
- Linha do tempo de atividades: inicializações, travamentos, backups e mudanças recentes numa só lista.
- Gráficos de recursos com escolha de 1 h / 24 h e limites claros.
