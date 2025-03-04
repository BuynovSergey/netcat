(function() {
CodeMirror.simpleHint = function(editor, getHints, auto, helpD,is_forced) {
	// We want a single cursor position.
	if (editor.somethingSelected()) {
		close();
		return;
	}
	if (editor.completionLocked) {
		return;
	}
	var result = getHints(editor);
	
	if (result) {
		result.not_moved = false;
	}
	if (editor.completionResult) {
		var old_res = editor.completionResult;
		if (result && old_res.from.line == result.from.line && old_res.from.ch == result.from.ch) {
			result.not_moved = true;
		}
	}
	editor.completionResult = result;

	if (!result || !result.list || !result.list.length) {
		close();
		return;
	}
	var completions = result.list;

	// When there is only one completion, use it directly.
	
	// не показываем, если слева от курсора нету значимых символов или маркера
    // т.е. список всех функций доступен только по Ctrl+Space
    if (!is_forced && (result.from.line == result.to.line && result.from.ch == result.to.ch) && result.no_marker) {
    	editor.completionResult = null;
    	close();
    	return;
    }

    var complete, sel, helpDia;
    
    buildWidget();
	renderWidget();

    function buildWidget() {
    	if (editor.complete) {
    		complete = editor.complete;
    		sel = editor.complete.sel;
    		helpDia = editor.helpDia;
    		return;
    	}

    	// Build the select widget
		complete = document.createElement("div");
		complete.className = "CodeMirror-completions";
		sel = complete.appendChild(document.createElement("select"));
		// Opera doesn't move the selection when pressing up/down in a
		// multi-select, but it does properly support the size property on
		// single-selects, so no multi-select is necessary.
		if (!window.opera) {
			sel.multiple = true;
		}
		document.body.appendChild(complete);
		editor.complete = complete;
		editor.complete.sel = sel;
		
		if(helpD) {
			helpDia = document.createElement("div");
			helpDia.className = "CodeMirror-completions";
			helpDia.style.padding = "10px";
			helpDia.style.maxWidth = "300px";
			helpDia.style.visibility = 'hidden';
			helpDia.style.backgroundColor = "white";
			document.body.appendChild(helpDia);
			editor.helpDia = helpDia;
		}
		
		CodeMirror.connect(sel, "blur", function () { close(); });
		
		sel.handleKeyDown = function(event) {
			var code = event.keyCode;
			// Enter
			if (code == 13) {
				CodeMirror.e_stop(event);
				pick();
			}
			// Escape
			else if (code == 27) {
				CodeMirror.e_stop(event); 
				close();
				editor.focus();
			}
			// Up and Down
			else if (code == 38 || code == 40) { 
				if (helpD) {
					setTimeout(function(){
						if (!editor.completionResult.list[sel.selectedIndex]) {
							// strange things in chrome
							sel.selectedIndex = 0;
						}
						showHelp(editor.completionResult.list[sel.selectedIndex]);
					}, 50);
				}
			} 
			// Ctrl
			else if (code == 17) {
				
			}
			// any other key
			else {
				close(); 
				editor.focus();
				// Pass the event to the CodeMirror instance so that it can handle things like backspace properly.
				editor.triggerOnKeyDown(event);
				
				if(!auto) {
					setTimeout(function(){
						CodeMirror.simpleHint(editor, getHints);
					}, 50);
				}
			}
		};
		
		sel.closeCompletion = function() {
			close();
		}
		
		CodeMirror.connect(sel, "keydown", sel.handleKeyDown);
		CodeMirror.connect(sel, "dblclick", pick);
		CodeMirror.connect(sel, 'click', function() {
			showHelp(editor.completionResult.list[sel.selectedIndex]);
		});
	}
	
	function renderWidget() {
		editor.complete.visible = true;
		///var pos = editor.cursorCoords();
		var pos = editor.charCoords(editor.completionResult.from);
		var moved = !editor.completionResult.not_moved;
        pos.x = pos.left; pos.y = pos.top; pos.yBot = pos.bottom;
		if (moved) {
			complete.style.left = pos.x - 3 + "px";
			complete.style.top = pos.yBot + 2 + "px";
		}

		sel.innerHTML = '';
		
		for (var i = 0; i < completions.length; ++i) {
			sel.options [i]= new Option(completions[i].name); 
		}
		sel.firstChild.selected = true
		sel.selectedIndex = 0;
		
		// If we're at the edge of the screen, then we want the menu to appear on the left of the cursor.
		var winW = window.innerWidth || Math.max(document.body.offsetWidth, document.documentElement.offsetWidth);
		var right = false;
		
		if(winW - pos.x <sel.clientWidth + 125) {
			complete.style.left = (pos.x - sel.clientWidth) + "px";
			right = true;
		}

		// Hack to hide the scrollbar.
		if (completions.length <= 10) {
			complete.style.width = (sel.clientWidth - 1) + "px";
		} else{
			complete.style.width = 'auto';
		}

		sel.size = Math.min(10, completions.length);
		complete.style.visibility = 'visible';
		if (helpD) {
			helpDia.style.left = right ? (pos.x - sel.clientWidth - 325) + "px" : (pos.x + 25 + complete.clientWidth) + "px";
			helpDia.style.top = pos.yBot + "px";
			helpDia.style.visibility = 'visible';
			showHelp(completions[0]);
		}
	}
	
	function showHelp(completion) {
		if (editor.complete.visible) {
			helpDia.innerHTML = completion.help;
			helpDia.style.visibility = completion.help ? 'visible' : 'hidden';
		}
	}
	
	function close() {
		if (!editor.complete) {
			return;
		}
		editor.complete.visible = false;
		editor.complete.style.visibility = 'hidden';
		if(helpD) {
			editor.helpDia.style.visibility = 'hidden';
		}
	}
	
	function pick() {
		editor.completionLocked = true;
		var cc = editor.completionResult.list[sel.selectedIndex];
		if (!cc || !cc.value) {
			console.log('no val!');
			close();
			return;
		}
		editor.replaceRange(cc.value, editor.completionResult.from, editor.completionResult.to);
		close();
		setTimeout(
			function(){
				editor.focus();
				if (cc.cursorPosition) {
					//console.log(result.from);
					editor.setCursor(editor.completionResult.from.line, editor.completionResult.from.ch + cc.cursorPosition);
				}
				editor.completionLocked = false;
			}, 50
		);
	}
	
	/*
	editor.completionFocusTime = new Date();
	
	setTimeout(function() {
		var now = new Date();
		var diff = now - editor.completionFocusTime;
		if (diff > 1490) {
			sel.focus();
		}
	},1500);
	*/
	
	return true;
};
})();
