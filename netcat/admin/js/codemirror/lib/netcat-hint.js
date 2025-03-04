(function () {
  function getStartOfName(str) {
    for (var i = str.length; --i;) {
      if (/[a-z0-9_]/i.test(str.charAt(i))) {
        continue;
      }
      return i;
    }
    if (str.charAt(0) === "$") {
      return 0;
    }
    return -1;
  }

  function detectMode(line, pos) {
    var prevLine = line.substr(0, pos),
      symbol = "";
    for (var curPos = pos - 1; curPos >= 0; curPos--) {
      symbol = prevLine.charAt(curPos);
      if (/[a-z0-9_]/i.test(symbol)) {
        continue;
      }
      break;
    }
    var pre_symbol = prevLine.charAt(curPos - 1);

    var mode = {
      start: curPos + 1,
      end: pos,
      type: 'function'
    };

    switch (symbol) {
      case "$":
        mode.type = 'variable';
        break;
      case ":":
        if (pre_symbol === ':') {
          mode.type = 'static';
        }
        break;
      case "%":
        mode.type = 'macros';
        break;
      case ">":
        if (pre_symbol === '-') {
          mode.type = 'method';
        }
        break;
    }

    if (mode.type === 'static' || mode.type === 'method') {
      mode.parent = prevLine.substring(getStartOfName(prevLine.substring(0, mode.start - 2)) + 1, mode.start - 2);
    }

    mode.str = prevLine.substr(mode.start);
    return mode;
  }

  function findVariants(string, data, type, parent) {
    if (!data) {
      return;
    }
    string = string.toLowerCase();
    var strlen = string.length;

    if (type) {
      if (!data[type]) {
        return;
      }
      data = data[type];
    }
    if (parent) {
      if (!data[parent]) {
        return;
      }
      data = data[parent];
    }

    if (!data._isSorted) {
      data.sort(function (a, b) {
        return a.key > b.key ? 1 : (a.key < b.key ? -1 : 0);
      });
      data._isSorted = true;
    }

    if (strlen === 0) {
      return data;
    }

    var res = [];

    for (var i = 0; i < data.length; i++) {
      var cd = data[i];
      if (cd.key.length >= strlen && cd.key.substr(0, strlen) === string) {
        res.push(cd);
      }
    }
    return res;
  }

  CodeMirror.netcatHint = function (editor) {
    // Find the token at the cursor
    var cur = editor.getCursor();

    var c_token = editor.getTokenAt(cur);
    if (c_token.state.mode !== 'php') {
      return null;
    }

    var mode = detectMode(editor.getLine(cur.line), cur.ch),
      str = mode.str,
      variants = null;

    if (editor.completionResult && editor.completionResult.type === mode.type && editor.completionResult.list) {
      var c_str = editor.completionResult.str;
      if (str.substring(0, c_str.length) === c_str) {
        editor.completionResult.list._isSorted = true;
        variants = findVariants(str, editor.completionResult.list);
      }
    }

    if (!variants) {
      variants = findVariants(str, editor.autoCompletionData, mode.type, mode.parent);
    }

    return {
      list: variants,
      from: {line: cur.line, ch: mode.start},
      to: {line: cur.line, ch: mode.end},
      no_marker: mode.type === 'function',
      type: mode.type,
      str: str
    }
  };

  CodeMirror.addCompletionData = function (data, target, type, parent) {
    if (!target[type]) {
      target[type] = parent ? {} : [];
    }
    target = target[type];
    if (parent) {
      if (!target[parent]) {
        target[parent] = [];
      }
      target = target[parent];
    }
    var data_arr = data;
    if (typeof data === 'string') {
      data = data.split(' ');
      data_arr = [];
      for (i = 0; i < data.length; i++) {
        data_arr.push({
          name: data[i],
          key: data[i].toLowerCase(),
          value: data[i] + (type === 'function' ? '()' : ''),
          help: 'something on ' + data[i]
        });
      }
    } else if (typeof data === 'object') {
      data_arr = [data];
    }
    for (var i = 0; i < data_arr.length; i++) {
      var cc = data_arr[i];
      if (!cc.key) {
        cc.key = cc.name.toLowerCase();
      }
      if (!cc.value) {
        cc.value = cc.name;
      }
      if (type === 'function' && !cc.cursorPosition) {
        var visible_sig = cc.value;
        visible_sig = visible_sig.replace(/\s*\(\s*/, '(\n   ');
        visible_sig = visible_sig.replace(/,\s/g, ',\n   ');
        visible_sig = visible_sig.replace(/\s*\)/, '\n)');
        cc.help = '<pre>' + visible_sig + '</pre>' + cc.help;
        cc.value = cc.name + '()';
        cc.cursorPosition = cc.name.length + 1;
      }
      target.push(cc);
    }
    target._isSorted = false;
  };

  CodeMirror.testArea = function testArea(dom_area, ar_areas) {
    if (!ar_areas) {
      return true;
    }
    for (var i = 0; i < ar_areas.length; i++) {
      if ($nc(dom_area).hasClass(ar_areas[i])) {
        return true;
      }
    }
    return false;
  };


  CodeMirror.importCompletionData = function (data, textareas) {
    textareas.each(function () {
      var el = $nc(this);
      var form_id = el.closest('form').attr('id');
      if (form_id === 'adminForm') {
        form_id = el.closest('form').attr('class');
      }
      if (!el.hasClass(form_id)) {
        el.addClass(form_id);
      }
      var full_class = form_id + '_' + el.attr('name');
      if (!el.hasClass(full_class)) {
        el.addClass(full_class);
      }

      for (var i = 0; i < data.length; i++) {
        var ce = data[i];
        if (CodeMirror.testArea(el, ce.areas)) {
          var c_data = $nc(el).data('autoCompletionData') || {};
          CodeMirror.addCompletionData(ce.completion, c_data, ce.type, ce.parent);
          $nc(el).data('autoCompletionData', c_data);
        }
      }
    });
  }
})();
