(function ($) {
    var todasAsVozes, bootstrap, chaveApi, idiomaSelecionado, vozSelecionada, audioTeste = document.createElement("AUDIO");
  
    // Chama a função principal ao carregar o DOM.
    $(quandoDomPronto);
  
    // Função executada quando o DOM está pronto.
    function quandoDomPronto() {
      chaveApi = $("#narrador_key").val() || ""; // Obtém a chave API do campo input.
      idiomaSelecionado = $("#narrador_lang").data("valor") || ""; // Obtém o idioma selecionado.
      vozSelecionada = $("#narrador_voice").data("valor") || ""; // Obtém a voz selecionada.
  
      // Carrega vozes e dados iniciais do bootstrap.
      Promise.all([carregarVozes(), carregarBootstrap()]).then(atualizar);
  
      // Escuta mudanças na chave API.
      $("#narrador_key").change(function () {
        chaveApi = $(this).val();
        carregarVozes().then(atualizar);
      });
  
      // Escuta mudanças no idioma.
      $("#narrador_lang").change(function () {
        idiomaSelecionado = $(this).val();
        atualizar();
      });
  
      // Escuta mudanças na voz.
      $("#narrador_voice").change(function () {
        vozSelecionada = $(this).val();
        atualizar();
      });
  
      // Testa a voz selecionada ao clicar no botão de teste.
      $("#narrador_test").click(function () {
        if (idiomaSelecionado && vozSelecionada) {
          $.get(
            "https://ws.readaloudwidget.com/test-voice?lang=" + idiomaSelecionado + "&voice=" + encodeURIComponent(vozSelecionada),
            function (res) {
              audioTeste.src = res.url; // Define o áudio retornado pelo teste.
              audioTeste.play(); // Reproduz o áudio.
            }
          );
        }
      });
    }
  
    // Função para carregar as vozes disponíveis.
    function carregarVozes() {
      if (!chaveApi) { // Caso a chave API esteja vazia.
        todasAsVozes = [];
        return Promise.resolve(); // Retorna uma promessa já resolvida.
      }
      return new Promise(function (concluir) {
        $.post({
          url: "https://ws.readaloudwidget.com/list-voices",
          data: JSON.stringify({ key: chaveApi }), // Envia a chave API.
          contentType: "application/json",
          dataType: "json",
          success: function (res) {
            todasAsVozes = res; // Salva as vozes recebidas.
            concluir(); // Resolve a promessa.
          },
          error: function () {
            alert("Falha ao carregar a lista de vozes. Verifique se a chave da API é válida."); // Mensagem de erro.
            todasAsVozes = [];
            concluir();
          }
        });
      });
    }
  
    // Função para carregar o código do bootstrap.
    function carregarBootstrap() {
      return new Promise(function (concluir) {
        $.get("https://assets.readaloudwidget.com/embed/code.html", function (res) {
          bootstrap = res; // Armazena o conteúdo do bootstrap.
          concluir(); // Resolve a promessa.
        });
      });
    }
  
    // Função para atualizar os campos do formulário e gerar o código final.
    function atualizar() {
      // Cria uma lista única de idiomas disponíveis.
      var idiomas = Array.from(new Set(todasAsVozes.map(function (voz) { return voz.lang; }))).sort();
      if (idiomaSelecionado && idiomas.indexOf(idiomaSelecionado) === -1) idiomaSelecionado = ""; // Reseta o idioma se inválido.
  
      // Atualiza o campo de seleção de idiomas.
      $("#narrador_lang").empty();
      $("<option>").val("").appendTo("#narrador_lang"); // Adiciona uma opção vazia.
      idiomas.forEach(function (idioma) {
        $("<option>").val(idioma)
          .text(idioma)
          .appendTo("#narrador_lang");
      });
      $("#narrador_lang").val(idiomaSelecionado);
  
      // Filtra e ordena as vozes disponíveis para o idioma selecionado.
      var vozes = todasAsVozes.filter(function (voz) { return voz.lang === idiomaSelecionado; }).sort(function (a, b) {
        return a.desc.localeCompare(b.desc);
      });
      if (vozSelecionada && vozSelecionada !== "grátis" && !vozes.some(function (voz) { return voz.name === vozSelecionada; })) {
        vozSelecionada = "";
      }
  
      // Atualiza o campo de seleção de vozes.
      $("#narrador_voice").empty();
      $("<option>").val("").appendTo("#narrador_voice"); // Adiciona uma opção vazia.
      $("<option>").val("grátis")
        .text("[grátis] Use vozes TTS fornecidas pelo navegador")
        .appendTo("#narrador_voice");
      vozes.forEach(function (voz) {
        $("<option>").val(voz.name)
          .text((voz.desc || voz.name) + " (" + voz.gender[0].toLowerCase() + ")")
          .appendTo("#narrador_voice");
      });
      $("#narrador_voice").val(vozSelecionada);
  
      // Exibe ou esconde o botão de teste.
      $("#narrador_test").toggle(!!vozSelecionada && vozSelecionada !== "grátis");
  
      // Gera o código final se todos os dados forem válidos.
      if (chaveApi && idiomaSelecionado && vozSelecionada) {
        var codigo = bootstrap
          .replace("${key}", chaveApi)
          .replace("${lang}", idiomaSelecionado)
          .replace("${voice}", vozSelecionada);
        $("#narrador_code").val(codigo);
      } else {
        $("#narrador_code").val("");
      }
    }
  })(jQuery);
  