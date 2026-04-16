<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12pt;
            color: #1a1a1a;
            line-height: 1.6;
        }

        #header {
            text-align: center;
            padding-bottom: 16px;
            border-bottom: 2px solid #cccccc;
            margin-bottom: 32px;
        }

        #header img {
            max-height: 70px;
            max-width: 240px;
        }

        #doc-titulo {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 24px;
            color: #1a1a1a;
        }

        #conteudo h1 { font-size: 15pt; margin: 20px 0 8px; }
        #conteudo h2 { font-size: 13pt; margin: 16px 0 6px; }
        #conteudo h3 { font-size: 12pt; margin: 14px 0 4px; }
        #conteudo p  { margin-bottom: 10px; text-align: justify; }
        #conteudo ul,
        #conteudo ol { margin: 8px 0 10px 24px; }
        #conteudo li { margin-bottom: 4px; }

        #footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9pt;
            color: #888888;
            border-top: 1px solid #cccccc;
            padding-top: 6px;
        }

        #conteudo {
            margin-bottom: 48px;
        }
    </style>
</head>
<body>

    <div id="footer">
        Documento gerado em {{ $dataGeracao }}
    </div>

    <div id="header">
        <img src="{{ base_path('public/assets/img/logo.png') }}" alt="Logo">
    </div>

    <div id="doc-titulo">{{ $titulo }}</div>

    <div id="conteudo">
        {!! $regulamento !!}
    </div>

</body>
</html>