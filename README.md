<div align="center">
  <h1>Sistema Benvenutti API</h1>
</div>

O **Sistema Benvenutti** é uma API responsável pelo gerenciamento do sistema de pontuação de arquitetos da Benvenutti.

Através do painel de gerenciamento (manager), é possível:

- Administrar participantes e usuários
- Controlar pontuações
- Convidar participantes
- Gerenciar edições de viagens e suas fotos
- Configurar SEO e slides da página do participante
- Visualizar ranking
- Editar e visualizar informações do programa

E através do painel do participante (painel), é possível:

- Visualizar informações do usuário autenticado
- Atualizar dados do usuário
- Listar o ranking dos 5 participantes mais próximos
- Consultar informações do programa
  
---

## Índice

- [Documentação](#documentacao)
- [Tecnologias Utilizadas](#tecnologias-utilizadas)
- [Arquitetura do Projeto](#arquitetura-do-projeto)
- [Como Executar o Projeto](#como-executar-o-projeto)

---

<h2 id="documentacao">Documentação:</h2>

A documentação da API foi realizada utilizando o [Swagger](https://sistema-benvenutti-api.oitoporoito.com.br/swagger.html)

<img width="1289" height="902" alt="image" src="https://github.com/user-attachments/assets/7ce45e80-8904-4a38-8c79-05b0113e2e75" />

---

<h2 id="tecnologias-utilizadas">Tecnologias Utilizadas:</h2>

- PHP (^8.1): linguagem backend utilizada
- Laravel Lumen (^10.0): micro-framework PHP baseado em Laravel
- JWT Auth (^2.3): para autenticação
- Tinify (^1.6): compressão de imagens via API
- Mail (^10.49): biblioteca para envio de emails

---

<h2 id="arquitetura-do-projeto">Arquitetura principal do Projeto:</h2>

```bash
Sistema-Benvenutti-API
│
├── app
│   ├── Console
│   ├── Events
│   ├── Exceptions
│   ├── Http
│   │   ├── Controllers    #Controladores responsáveis pelas requisições
│   │   ├── Middleware     #Interceptação e validação de requisições
│   ├── Jobs               #Tarefas assíncronas
│   ├── Listeners          #Escutam eventos e executam ações
│   ├── Models             #Representação das tabelas do banco (Eloquent)
│   ├── Providers          #Configuração de pacotes
│   ├── Services           #Regras de negócio
├── bootstrap              #Inicialização do framework
├── config                 #Arquivos de configuração
├── database               #Migrations, seeds e factories
├── public                 #Ponto de entrada
│   ├── docs               #Arquivos da documentação Swagger (JSON)
├── resources              #Arquivos auxiliares
├── routes                 #Definição das rotas/endpoints da API
├── storage                #Arquivos gerados (logs, cache e etc.)
├── tests
│

```

---

<h2 id="como-executar-o-projeto">Como Executar o Projeto:</h2>

Clone o repositório:

```bash
git clone https://github.com/Octal-web/Sistema-Benvenutti-API.git
cd Sistema-Benvenutti-API
```

Crie um arquivo `.env`, veja o arquivo `.env.example` para orientação

Instale as dependências:

```bash
composer install
```

Rode o projeto:

```bash
php -S localhost:8000 -t public
```

Acesse o Swagger:

```bash
http://localhost:8000/swagger.html
```
