(function(window, document, $) {
  var
    evalURL = '/eval/index.php',
    editor;

  // HELPERS
  var
    setOutput = function(text) {
      var isError = !! arguments[1],
          $output = $('#output');

      // Remove error classes if any
      $output.html(text).removeClass('error');

      if (isError) $output.addClass('error');
    },

    sendingCode = function(code) {
      return $.ajax({
        type: 'POST',
        url: evalURL,
        data: { code: code},
        dataType: 'json'
      });
    },

    // Helper to show the fatal errors nicely
    // Returns a list of the error text and line number that
    // generated the error.
    // Note: Implementation is fairly naive but works
    getPrettyFatalErrorMessage = function (responseText) {
      if (! responseText.length) return;

      var text = responseText,
          tokensToReplace = ['\n', /<br \/>/g, /<b>/g,
                              /<\/b>/g, /(Fatal error: +)/g],
          splitTokens, err, line, lineNum;

      // If the error message doesn't contain 'fatal error',
      // then just print it
      if (! responseText.toLowerCase().indexOf('fatal error')) {
        return [responseText, 1];
      }

      $.each(tokensToReplace, function (idx, val) {
        text = text.replace(val, '');
      });

      splitTokens = text.split('in');
      err = splitTokens[0].trim();
      splitTokens = text.split('on');
      line = splitTokens[1].trim();

      text = (err + ' on ' + line).trim();

      lineNum = line.split(' ');
      lineNum = Number(lineNum[1]);

      return [text, lineNum];
    },

    // Navigate to the line with the error
    showLineError = function (line) {
      editor.gotoLine(line, 0, true);
    },

    // Handles the sending of the code to the eval server
    processCode = function () {
      var code = editor.getValue();

      if (! code.length) {
        setOutput('Please supply some code...');
        return;
      }

      sendingCode(code)
        .done(function (res) {
          console.log(res);
          if (! res) return;

          var result    = res.result,
              error     = res.error,
              errorMsg  = '';

          if (error.length != 0) {
            if (error.line && error.message) {
              // Highlight the error line
              showLineError(error.line);

              // Show the error message
              errorMsg = 'Line ' + error.line + ': ';
            }

            errorMsg += error.message;

            setOutput(errorMsg, true);
            return;
          }

          setOutput(result);
        })
        .fail(function (error) {
          if (! error) return;

          var textLine = getPrettyFatalErrorMessage(error.responseText);

          setOutput(textLine[0], true);
          showLineError(textLine[1]);
        });
    };

  // Local storage helpers
  var
      saveCode = function () {
        if (window.localStorage) {
          var code = editor.getValue();
          localStorage.setItem('code', code);
        }
      },
      loadSavedCode = function () {
        // Preload where you last left off
        if (window.localStorage) {
          var greeting = 'echo "We\'re running php version: " . phpversion();',
              result = localStorage.getItem('code'),
              code   = ! result ? greeting : result;
          editor.setValue(code)
          editor.clearSelection();
        }
      };

  //load the editor
  var editor = ace.edit("editor");
  editor.setTheme("ace/theme/monokai");
  editor.getSession().setMode({path: "ace/mode/php", inline: true});

  loadSavedCode();

  editor.commands.addCommand({
    name: 'sendCode',
    bindKey: {win: 'Ctrl-Enter',  mac: 'Command-Enter'},
    exec: function(editor) {
        processCode();
    },
    readOnly: true // false if this command should not apply in readOnly mode
  });
  editor.commands.addCommand({
    name: 'saveCode',
    bindKey: {win: 'Ctrl-S',  mac: 'Command-S'},
    exec: function(editor) {
        saveCode();
    },
    readOnly: true // false if this command should not apply in readOnly mode
  });

  // Remember the code in the editor
  // before navigating away
  $(window).unload(saveCode);

  $('.run').click(processCode);

  
})(window, document, window.jQuery);