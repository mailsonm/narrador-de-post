# narrador-de-post

Um plugin simples e poderoso para adicionar narração aos posts do WordPress. Personalize as configurações de idioma, voz e modo de exibição diretamente pelo painel administrativo.

---

## 📜 Funcionalidades

- **Narração Automática ou Manual**: Escolha entre narração automática para todos os posts ou adicione manualmente onde preferir.
- **Painel de Configurações**: Gerencie o idioma, voz e modo de funcionamento sem sair do WordPress.
- **Compatível com Todos os Temas**: Integra-se facilmente com qualquer tema do WordPress.
- **Personalização CSS**: Adapte o estilo do botão para combinar com o design do seu site.

---

## 🚀 Instalação

1. Faça o download do plugin ou clone este repositório.
2. Acesse o painel administrativo do WordPress.
3. Vá em **Plugins** > **Adicionar Novo** > **Enviar Plugin**.
4. Faça o upload do arquivo ZIP do plugin e clique em **Instalar Agora**.
5. Ative o plugin.

---

## ⚙️ Configuração

1. Acesse **Configurações** > **Narrador de Post** no painel administrativo.
2. Configure:
   - **Modo**: Ativar ou desativar a narração, definir como automático ou manual.
   - **Chave de API**: (opcional) Use sua chave para integração com serviços externos.
   - **Idioma**: Escolha o idioma da narração (ex.: `pt-BR`).
   - **Voz**: Selecione o estilo de voz disponível no serviço de narração.

---

## 🖥️ Capturas de Tela

### Configurações do Plugin
![Tela de Configuração](https://via.placeholder.com/800x400.png?text=Tela+de+Configura%C3%A7%C3%A3o+do+Plugin)

### Botão no Post
![Botão de Narração](https://via.placeholder.com/800x400.png?text=Bot%C3%A3o+de+Narra%C3%A7%C3%A3o+no+Post)

---

## 📋 Como Usar

### Narração Automática
1. Configure o plugin no modo **Automático**.
2. O botão de narração será exibido automaticamente em todos os posts.

### Narração Manual
1. Configure o plugin no modo **Manual**.
2. Insira o seguinte shortcode onde desejar ativar a narração:
   ```php
   [narrador_post]
   ```

---

## 💻 Desenvolvedores

- **Personalização de CSS**: O arquivo `narrador-de-post.css` pode ser editado para ajustar o estilo do botão.
- **Hooks Disponíveis**:
  - `the_content`: Filtra o conteúdo do post para adicionar o widget de narração.

### Estrutura do Plugin
```
📂 narrador-de-post
├── narrador-de-post.php
├── narrador-de-post.css
└── readme.md
```

---

## 📌 Requisitos

- WordPress 5.0 ou superior.
- PHP 7.4 ou superior.

---

## 🛠️ Contribuindo

Contribuições são bem-vindas! Siga os passos abaixo para colaborar:

1. Faça um fork do projeto.
2. Crie uma branch para sua funcionalidade ou correção: `git checkout -b minha-feature`.
3. Faça o commit das mudanças: `git commit -m 'Adicionei minha funcionalidade'`.
4. Envie para o repositório remoto: `git push origin minha-feature`.
5. Abra um Pull Request.

---

## 📄 Licença

Este projeto está licenciado sob a [MIT License](LICENSE).

---

## 📞 Suporte

Se você tiver dúvidas ou problemas, entre em contato:
- **Email**: suporte@seudominio.com
- **GitHub Issues**: [Abrir uma nova issue](https://github.com/mailsonm/narrador-de-post/issues)

---

Feito com ❤️ para tornar seus posts mais acessíveis!
