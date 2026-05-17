<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4 portrait;
            /*
                DomPDF ignora @page margin na maioria das versoes.
                Definimos margin: 0 aqui para evitar margem dupla.
                As margens reais sao controladas pelo padding do body.
            */
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            color: #1a1a1a;
            line-height: 1.4;

            /*
                Margem padrao Word: 2,54 cm laterais (25.4mm).
                Topo e rodape: 20mm para acomodar o header/footer fixo (15mm)
                mais uma folga visual de ~5mm entre a linha e o texto.
            */
            padding: 20mm 25.4mm;
        }

        /* ── CABECALHO ───────────────────────────────────────────────────────
           position:fixed no DomPDF usa coordenadas absolutas da pagina (nao
           do body). Por isso left:0/right:0 vai da borda fisica da folha.
           O padding lateral espelha o padding do body para alinhar o texto.
        */
        #header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 15mm;
            padding: 8mm 25.4mm 0 25.4mm;
            text-align: right;
            font-size: 9pt;
            color: #9CA3AF;
            font-family: Arial, 'DejaVu Sans', sans-serif;
        }

        /* ── RODAPE ──────────────────────────────────────────────────────────*/
        #footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 15mm;
            padding: 3mm 25.4mm 0 25.4mm;
            text-align: center;
            font-size: 9pt;
            color: #9CA3AF;
            font-family: Arial, 'DejaVu Sans', sans-serif;
        }

        .page-num::after   { content: counter(page); }

        /* ── CONTEUDO PRINCIPAL ───────────────────────────────────────────────
           margin-top/bottom empurra o texto para nao ficar sob o header/footer.
        */
        #conteudo {
            margin-top: 5mm;
            margin-bottom: 5mm;
        }

        #conteudo h1 {
            font-size: 13pt;
            font-weight: bold;
            margin: 20px 0 8px 0;
            text-transform: uppercase;
            page-break-after: avoid;
        }

        #conteudo h2 {
            font-size: 13pt;
            font-weight: bold;
            margin: 16px 0 6px 0;
            page-break-after: avoid;
        }

        #conteudo h3 {
            font-size: 11pt;
            font-weight: bold;
            margin: 16px 0 4px 0;
            page-break-after: avoid;
        }

        #conteudo p {
            font-size: 11pt;
            margin-bottom: 8px;
            text-align: justify;
        }

        #conteudo ul {
            margin: 4px 0 10px 0;
            padding-left: 0;
            list-style: none;
        }

        #conteudo ul li {
            font-size: 11pt;
            margin-bottom: 5px;
            padding-left: 18px;
            position: relative;
            text-align: justify;
        }

        #conteudo ul li::before {
            content: "\2022";
            position: absolute;
            left: 4px;
        }

        #conteudo ol {
            margin: 4px 0 10px 22px;
        }

        #conteudo ol li {
            font-size: 11pt;
            margin-bottom: 5px;
            text-align: justify;
        }

        #conteudo strong { font-weight: bold; }
        #conteudo em     { font-style: italic; color: #6B7280; }
    </style>
</head>
<body>

    <div id="header">BENVENUTTI EXPERIENCE &mdash; Termo de Ades&atilde;o</div>

    <div id="footer">
        P&aacute;gina <span class="page-num"></span> de {{ isset($totalPaginas) ? $totalPaginas : '0' }}</span>
    </div>

    <div id="conteudo">
        {!! $regulamento !!}
    </div>

</body>
</html>