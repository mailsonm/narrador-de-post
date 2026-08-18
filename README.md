# 🎙️ Narrador de Post (v2.0.0 Híbrido)

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg?style=flat-square&logo=wordpress)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20|%208.0%20|%208.1%20|%208.2%20|%208.3-777BB4.svg?style=flat-square&logo=php)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](https://opensource.org/licenses/MIT)
[![TDD Tested](https://img.shields.io/badge/Tests-100%25%20Passing-brightgreen.svg?style=flat-square)](tests/test_narrador_de_post.php)

> **Narrador de Post** é um plugin para WordPress moderno, leve e híbrido que adiciona um leitor de áudio em seus artigos para conversão de texto em fala (TTS). Possui modo **100% gratuito** e suporte a **IA Neural de alta fidelidade** (OpenAI TTS).

---

## 🌐 Idiomas / Languages / Idiomas

- [🇧🇷 Português (PT-BR)](#-português-pt-br)
- [🇺🇸 English (EN)](#-english-en)
- [🇪🇸 Español (ES)](#-español-es)

---

## 🇧🇷 Português (PT-BR)

### 💡 Por que usar o Narrador de Post?
Oferecer uma versão em áudio dos seus artigos aumenta drasticamente o tempo de permanência no site, melhora o SEO e garante acessibilidade para pessoas com deficiência visual ou usuários que preferem ouvir conteúdos enquanto realizam outras tarefas.

### 🌟 Modos de Operação (Arquitetura Híbrida)

#### 1. Modo Gratuito (Vozes Neurais do Navegador)
* **Zero Custo:** Não exige nenhuma chave de API e não consome créditos.
* **Vozes Naturais:** Detecta automaticamente as melhores vozes neurais instaladas no dispositivo do visitante (ex: *Microsoft Francisca/Antonio Online (Natural)* no Edge/Windows, vozes de alta qualidade do Google no Chrome e Siri no Safari/iOS).
* **Anti-Congelamento de 15s:** Algoritmo que divide o texto em blocos por pontuação, contornando o limite de 15 segundos da Web Speech API nos navegadores Chromium.
* **Player Completo:** Botão Ouvir, Pausar, Continuar, Parar, Seletor de Velocidade (0.75x, 1x, 1.25x, 1.5x, 2x) e Barra de Progresso em tempo real.

#### 2. Modo IA de Alta Fidelidade (OpenAI TTS - BYOK)
* **Qualidade de Podcast:** Narração humana ultrarrealista com vozes de estúdio (Alloy, Echo, Fable, Onyx, Nova, Shimmer).
* **Bring Your Own Key (BYOK):** O administrador utiliza sua própria chave da OpenAI sem depender de intermediários.
* **Cache Inteligente em MP3:** O áudio é sintetizado uma única vez por post e salvo como `.mp3` no seu WordPress (`wp-content/uploads/narrador-de-post/`), garantindo carregamento instantâneo e **zero custo de API por visualização de página**.

---

### 🔑 Manual: Como Obter sua Chave de API OpenAI (Opcional)

Se você optar pelo **Modo IA**, siga o passo a passo para gerar sua chave:

1. Acesse o painel de desenvolvedor da OpenAI: [platform.openai.com/api-keys](https://platform.openai.com/api-keys).
2. Crie uma conta ou faça login.
3. No menu lateral, clique em **API keys** e depois no botão **"+ Create new secret key"**.
4. Dê um nome à chave (ex: `WordPress Narrador`) e copie o código exibido (iniciado por `sk-proj-...`).
5. No painel do seu WordPress, vá em **Narrador de Post**, cole a chave no campo **OpenAI API Key**, selecione a voz desejada e clique em **Salvar Configurações**.

---

### 🧩 Integração com o Auto Shortcode Inserter

O Narrador de Post disponibiliza o shortcode:
```text
[narrador_de_post]
```
Você pode utilizar o plugin **[Auto Shortcode Inserter](https://github.com/mailsonm/auto-shortcode-inserter)** para injetar automaticamente o leitor de áudio no topo de todos os seus posts de forma centralizada!

---

## 🇺🇸 English (EN)

### 💡 Why use Post Narrator?
Providing an audio version of your blog posts increases reader engagement, boosts SEO, and ensures accessibility for visually impaired readers or multitaskers.

### 🌟 Key Features (Hybrid Architecture)

#### 1. Free Mode (Browser Neural Voices)
* **100% Free:** Zero API keys required, zero running costs.
* **Natural Voice Engine:** Automatically selects the best neural/natural voices on the visitor's device.
* **Anti-Freeze Chunking:** Sentence-based chunking that prevents Chromium's 15-second speech synthesis timeout.
* **Complete Audio Player:** Play, Pause, Resume, Stop, Speed selector (0.75x to 2x), and progress bar.

#### 2. Studio AI Mode (OpenAI TTS - BYOK)
* **Human-like Audio:** Realistic studio voices (Nova, Alloy, Echo, Onyx, Shimmer).
* **Local MP3 Caching:** Synthesized once and cached in `wp-content/uploads/narrador-de-post/`, ensuring instant playback and zero recurring API costs per pageview.

---

## 🇪🇸 Español (ES)

### 💡 ¿Por qué usar Narrador de Post?
Ofrecer una versión en audio de tus artículos mejora la accesibilidad, aumenta el tiempo de permanencia en tu sitio web y optimiza el SEO.

### 🌟 Características Principales (Arquitectura Híbrida)
* **Modo Gratuito (Voces Neuronales):** Sin costes de API. Utiliza sintetizadores nativos de alta calidad.
* **Modo IA de Estudio (OpenAI TTS):** Voces humanas hiperrealistas con caché persistente en MP3.
* **Reproductor Moderno:** Controles de reproducción, velocidad (0.75x - 2x) y barra de progreso.

---

## 🧪 Testes Automatizados / Automated Tests (TDD)

Execução dos testes unitários em PHP 8.3 via Docker:
```bash
docker run --rm -v "${PWD}:/app" -w /app php:8.3-cli php tests/test_narrador_de_post.php
```

---

## 👤 Autor

**Mailson Maia Alves**  
* GitHub: [@mailsonm](https://github.com/mailsonm)

## 📄 Licença

Distribuído sob a licença [MIT](LICENSE).
