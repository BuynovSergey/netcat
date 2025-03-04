/**
 * Original PHP Engine: http://rmcreative.ru/blog/post/tipograf
 * JS Lite Version
 */

//typographus object
var Typographus_Lite = new Object();
//special characters
Typographus_Lite.sp_chars = {
  nbsp     : '&nbsp;',
  lnowrap  : '<span style="white-space:nowrap">',
  rnowrap  : '</span>',
  lquote   : '«',
  rquote   : '»',
  lquote2  : '„',
  rquote2  : '“',
  mdash    : '—',
  ndash    : '–',
  minus    : '–', // width equals to +, present in every font
  hellip   : '…',
  copy     : '©',
  trade    : '™',
  apos     : '&#39;',   // ñì. http://fishbowl.pastiche.org/2003/07/01/the_curse_of_apos
  reg      : '®',
  multiply : '&times;',
  frac_12  : '&frac12;',
  frac_14  : '&frac14;',
  frac_34  : '&frac34;',
  plusmn   : '±',
  rarr     : '?',
  larr     : '?',
  rsquo    : '&rsquo;'
};

//safeblocks (as parts of regular expressions)
//ADD YOUR SAFEBLOCKS HERE AS 'start' : 'end' PAIR
Typographus_Lite.safeblocks = {
  '<safeblock>' : '<\\/safeblock>',
  '<pre[^>]*>' : '<\\/pre>',
  '<style[^>]*>' : '<\\/style>',
  '<script[^>]*>' : '<\\/script>',
  '<code[^>]*>' : '<\\/code>',
  '<!--' : '-->',
  '<\\?php' : '\\?>',
  '<drupal6>' : '<\\/drupal6>',
  '<php>' : '<\\/php>',
  '<cpp>' : '<\\/cpp>',
  '<object>' : '<\\/object>',
  '<javascript>' : '<\\/javascript>',
  '<qt>' : '<\\/qt>'
};


Typographus_Lite.safeblock_storage = [];

//safeblock stacking
__stack = function (match) {
  //get length
  var i = Typographus_Lite.safeblock_storage.length;
  //add match
  Typographus_Lite.safeblock_storage[i] = match;
  //return replacement
  return "<" + i + ">";
}

//safeblock processing
Typographus_Lite.remove_safeblocks = function(str) {
  //empty storage
  this.safeblock_storage = [];
  var pattern = '(';
  for (var key in this.safeblocks) {
    pattern += "(" + key + "(.|\\n)*?" + this.safeblocks[key] + ")|";
  }
  pattern += '<[^>]*[\\s][^>]*>)';
  str = str.replace(RegExp(pattern, "gim"), __stack);
  return str;
}

//safeblock returning
Typographus_Lite.return_safeblocks = function(str) {
  for (var i=0; i<this.safeblock_storage.length; i++) {
    var block = "<" + i + ">";
    str = str.replace(block, this.safeblock_storage[i]);
  }
  return str;
}


/**
 *
 *  This function calls typo_text to process string str
 *
 */
Typographus_Lite.process = function(str) {
  str = this.remove_safeblocks(str);
  str = this.typo_text(str);
  str = this.return_safeblocks(str);
  return str;
}


/**
 *
 * This function applies array of rules to string str
 *
 */
Typographus_Lite.apply_rules = function(rules, str) {
  for (var key in rules) {
    var rule = new RegExp(key, "gim"); //with global, case-insensitive, multiline flags
    var newstr = rules[key];
    str = str.replace(rule, newstr);
  }
  return str;
}	


/**
 *
 * The main typographus function
 *
 */
Typographus_Lite.typo_text = function(str) {
  var sym = this.sp_chars;
  var html_tag = '(?:<.*?>)';
  var hellip = '\\.{3,5}';
  var word = '[a-zA-Z_àáâãäå¸æçèéêëìíîïðñòóôõö÷øùüûúýþÿÀÁÂÃÄÅ¨ÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÜÛÚÝÞß0123456789]';
  var phrase_begin = "(?:" + hellip + "|" + word + '|\\n)';
  var phrase_end = '(?:[)!?.:;#*\\\]|$|'+ word + '|' + sym['rquote'] + '|' + sym['rquote2'] + '|&quot;|"|' + sym['hellip'] + '|' + sym['copy'] + '|' + sym['trade'] + '|' + sym['apos'] + '|' + sym['reg'] + '|\\\')';
  var any_quote = "(?:" + sym['lquote'] + "|" + sym['rquote'] + "|" + sym['lquote2'] + "|" + sym['rquote2'] + "|&quot;|\\\")";
  //symbols
  var rules_symbols = {};
  //(c)
  rules_symbols['\\((c|ñ)\\)'] = sym['copy'];
  //(r)
  rules_symbols['\\(r\\)'] = sym['reg'];
  //tm
  rules_symbols['\\(tm\\)'] = sym['trade'];
  //hellip
  rules_symbols[hellip] = sym['hellip'];
  //+-
  rules_symbols['([^\\+]|^)\\+-'] = '$1' + sym['plusmn'];
  //->
  rules_symbols['([^-]|^)-(>|&gt;)'] = '$1' + sym['rarr'];
  //<-
  rules_symbols['([^<]|^)(<|&lt;)-'] = '$1' + sym['larr'];
  //quotes
  var rules_quotes = {};
  rules_quotes['([^"]\\w+)"(\\w+)"'] = '$1 "$2"';
  rules_quotes['"(\\w+)"(\\w+)'] = '"$1" $2';
  rules_quotes["(" + html_tag + "*?)(" + any_quote + ")(" + html_tag + "*" + phrase_begin + html_tag + "*)"] = '$1' + sym['lquote'] + '$3';
  rules_quotes["(" + html_tag + "*(?:" + phrase_end + "|[0-9]+)" + html_tag + "*)(" + any_quote + ")(" + html_tag + "*" + phrase_end + html_tag + "*|\\s|$$|\\n|[,<-])"] = '$1' + sym['rquote'] + '$3';

  //main rules
  var rules_main = {};
  //fix dashes
  rules_main[' +(?:--?|—|&mdash;)(?=\\s)'] = sym['nbsp'] + sym['mdash'];
  rules_main['^(?:--?|—|&mdash;)(?=\\s)'] = sym['mdash'];
  rules_main['(?:^|\\s+)(?:--?|—|&mdash;)(?=\\s)'] = "\n" + sym['nbsp'] + sym['mdash'];
  //fix digit-dash
  rules_main['(\\d{1,})(-)(?=\\d{1,})'] = '$1' + sym['ndash'];
  //glue percent
  rules_main['([0-9]+)\\s+%'] = '$1%';

  //apply different rules
  str = this.apply_rules(rules_quotes, str);
  str = this.apply_rules(rules_main, str);
  str = this.apply_rules(rules_symbols, str);

  return str;
}
