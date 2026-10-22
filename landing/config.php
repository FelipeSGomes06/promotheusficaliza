<?php

declare(strict_types=1);

// Configuração da landing pública (único ponto a editar). Nada aqui é segredo: tudo vai para o HTML, escapado.

return [
    // Endereço da aplicação. A landing só aponta para ele; quem decide login e acesso é a própria aplicação.
    'sistema_url' => '/promotheusfiscaliza/',

    // Endereço público canônico da landing (canonical, og:url e a imagem de compartilhamento, que precisa de URL
    // absoluta). Termina com "/".
    'url_publica' => 'https://fiscaliza.devfelipegomes.com.br/',

    'autor' => [
        'nome' => 'Felipe Gomes',
        'url' => 'https://devfelipegomes.com.br/',
    ],

    // Vídeo da seção "Para o avaliador". Enquanto 'src' for null (ou o arquivo local não existir), a página mostra
    // "Vídeo de apresentação em breve." e não pede nenhum arquivo. Caminhos começando com "/" são arquivos desta
    // landing, relativos a landing/public (ex.: '/landing-assets/promotheus/video/apresentacao.mp4').
    'video' => [
        'src' => null,
        'tipo' => 'video/mp4',
        'legendas' => null, // ex.: '/landing-assets/promotheus/video/apresentacao.pt-BR.vtt'
        'capa' => null,     // ex.: '/landing-assets/promotheus/video/capa.webp' (16:9)
    ],

    // Ajustes da marca 3D da hero.
    'hero' => [
        'montagem' => true,  // false: mostra a marca pronta, sem a sequência de montagem
        'duracao' => 4.0,    // segundos da sequência completa (as etapas mantêm a proporção)
        'ponteiro' => true,  // depois de montada, a marca acompanha o mouse (nunca no toque)
    ],
];
