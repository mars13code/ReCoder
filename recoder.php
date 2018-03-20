<?php 
/*
Template Name: ReCoder
Template Post Type: page, post
*/

$monacoProxy = $_REQUEST["monaco-editor-worker-loader"] ?? "";
if ($monacoProxy == "proxy.js")
{
    echo 
<<<CODEJS
self.MonacoEnvironment = {
	baseUrl: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.10.1/min/'
};
importScripts('https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.10.1/min/vs/base/worker/workerMain.js');
CODEJS;

    exit;
}

$loadUrl = trim(strip_tags($_REQUEST["load"] ?? ""));
if ($loadUrl != "")
{
    $historyCode = trim(file_get_contents($loadUrl));
}
?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<title>MARS13 ReCoder</title>
    <style>
body * {
    box-sizing:border-box;
    font-size:16px;
}    
a {
    display:inline-block;
    padding:0.5rem;
    border:1px solid #BDBDBD;
    margin:0.5em;
    text-decoration: none;
    background-color: #cccccc;
}
.ligne {
    display:flex;
    width:100%;
}
.colonne {
    width:50%;
}
.codeBox {
    width:40%;
}
.previewBox {
    width:60%;
}
iframe {
    width:100%;
    height:92vmin;
    border:1px solid #000000;
    resize:both;
}

.ed {
    padding:0;
    margin:0;
    margin-bottom: 1vmin;
    width:100%;
    height:30vmin;
    border:none;
    background-color:#aaaaaa;
    resize:vertical;
}
.ed1  {
    height:35vmin;
}
.ed2  {
    height:30vmin;
}
.ed3  {
    height:25vmin;
}
.lightbox {
	position:fixed;
	top:0;
	left:0;
	width:100%;
	height:100%;
	background-color:rgba(0,0,0,0.8);
	padding:1rem;
	display:none;
}
.lightbox.show {
	display:block;			
}		
.lightbox textarea {
	margin:0 auto;
	width:90%;
	height:80%;
	display:block;
}
.toolbar {
    background-color: #FAFAFA;
    display:flex;
    padding:0;
    align-items:baseline;
    font-family:monospace;
}
.toolbar strong {
    color:#ffffff;
    text-shadow: 2px 4px 8px #aaaaaa;
}
.lightbox a, .toolbar a {
    text-align:center;
    border-radius:1rem 0px 1rem 0px;
    background-color: #ffffff;
    padding:0.5rem 1rem;
    display:block;
    color:#004D40;
    font-size:0.8rem;
}
.toolbar a.actif {
    background-color:#00FF00;
}
.toolbar .go {
    font-weight:900;
}
.toolbar input {
    margin-top:0.4rem;
    padding:0.5rem;
    width:80px;
    height:2rem;
    font-size:0.8rem;
}

.previewBox {
}
    </style>
	</head>
	<body>
		
<section>
    <div class="toolbar">
        <strong><a href="/">MARS13.FR * ReCoder</a></strong>
        <pre> </pre>
        <a class="go" href="#go">GO (<small>press to replay</small>)</a>
        <input type="number" name="speed" value="50" step="10">
        <pre> ms  </pre>
        <a class="step" href="#">0 / 0</a>
        <pre> </pre>
        <a class="codeH" href="#codeH">HTML</a>
        <a class="codeC" href="#codeC">CSS</a>
        <a class="codeJ" href="#codeJ">JS</a>
        <pre> </pre>
        <a class="history" href="#history">history</a>
        <a class="reset" href="#go">reset</a>
    </div>
    <div class="ligne">
        <div class="colonne codeBox">
    <div id="container" class="ed ed1"></div>
    <div id="container2" class="ed ed2"></div>
    <div id="container3" class="ed ed3"></div>
        </div>
        <div class="colonne previewBox">
            <iframe class="preview"></iframe>
        </div>
    </div>
	<div class="lightbox">
		<div><a href="#close" class="closeLB">close</a><a href="#replace" class="replaceHistory">REPLACE HISTORY</a><a class="step" href="#">0 / 0</a>
</div>
		<textarea name="history"><?php echo $historyCode ?? "" ?></textarea>
	</div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.10.1/min/vs/loader.js"></script>
    <script>

var htmlRange   = [];
var cssRange    = [];
var jsRange     = [];

var tabRange    = [];
var replayIndex = 0;
var curTab      = "html";

var recordMode  = true;

var htmlModel   = null;
var cssModel    = null;
var jsModel     = null;

var ajaxGo      = true; 
var ajaxUrl     = "";
var ajaxLast    = Date.now();
var updateAjax  = function () 
{
    var now = Date.now();
    if ( recordMode 
        || ((now - ajaxLast) > 250) 
        || (replayIndex - tabRange.length > -5))
    {
    	var preview     = document.querySelector(".preview");
    	var previewCode =       ''
    	                        + '<' + 'style' + '>'
    	                        + cssModel.getValue()
    	                        + '<' + '/style' + '>'
    	                        + htmlModel.getValue() 
    	                        + '<' + 'script' + '>'
    	                        //+ 'try { '
    	                        + jsModel.getValue()
    	                        //+ ' } catch(error) { console.log(error) } '
    	                        + '<' + '/script' + '>'
    	                        ;
        preview.srcdoc  = previewCode;
        ajaxLast = now;
    }
    
    if (!ajaxGo) return;
    
    ajaxGo = false;
    
    
	if (ajaxUrl != "")
	{
        var form = new FormData();
        form.append("action", "update");
        form.append("html", htmlModel.getValue());
        form.append("js", jsModel.getValue());
        form.append("css", cssModel.getValue());
        form.append("csrf-token", csrfToken);

	    fetch("html/paf.php", {
	        method: "POST",
        	body: form
    	})
    	.then(function(response) {
        	ajaxGo      = true;
        	//preview.src = "html/test.html?reload=" + Math.random();
        	//console.log(response);
    	});
			
	}
    
}
// https://github.com/Microsoft/monaco-editor-samples/blob/master/sample-editor/index.html
require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.10.1/min/vs' }});
// Before loading vs/editor/editor.main, define a global MonacoEnvironment that overwrites
// the default worker url location (used when creating WebWorkers). The problem here is that
// HTML5 does not allow cross-domain web workers, so we need to proxy the instantiation of
// a web worker through a same-domain script
window.MonacoEnvironment = {
	getWorkerUrl: function(workerId, label) {
		return '?monaco-editor-worker-loader=proxy.js';
	}
};

require(['vs/editor/editor.main'], function() {
    var curModel    = null;
    var jsonReplay  = "";
	var editor = monaco.editor.create(document.getElementById('container'), {
    	theme: "vs-dark"
	});
	var editor2 = monaco.editor.create(document.getElementById('container2'), {
    	theme: "vs-dark"
	});
	var editor3 = monaco.editor.create(document.getElementById('container3'), {
    	theme: "vs-dark"
	});
	
	htmlModel = monaco.editor.createModel("", "html", "page.html");
	cssModel = monaco.editor.createModel("", "css", "site.css");
	jsModel = monaco.editor.createModel("", "javascript", "site.js");
	
	curModel = htmlModel;
	editor.setModel(htmlModel);
	editor2.setModel(cssModel);
	editor3.setModel(jsModel);
	
	var saveUpdate = function (rgbox, tab)
	{
	        var sav = {};
	        sav.tab = tab;
    	    //console.log(rgbox);
            sav.nb  = tabRange.length;   // FOR POST-PROD
            sav.txt = rgbox.text;
    	    sav.rg  = {  "sln" : rgbox.range.startLineNumber, 
                         "eln" : rgbox.range.endLineNumber, 
                         "sc"  : rgbox.range.startColumn, 
                         "ec"  : rgbox.range.endColumn };
	        tabRange.push(sav);

            curTab = tab;
	        updateTab();
            updateStep();
	};
	
	var editorUpdate = function(e) {
	    if (!recordMode) return;
	    for(rgbox of e.changes)
	    {   
	        saveUpdate(rgbox, "html");
	    }
	    
        updateAjax();        

	};
	var editorUpdate2 = function(e) {
	    if (!recordMode) return;
	    for(rgbox of e.changes)
	    {
	        saveUpdate(rgbox, "css");
	    }
	    
        updateAjax();        

	};
	var editorUpdate3 = function(e) {
	    if (!recordMode) return;
	    for(rgbox of e.changes)
	    {   
	        saveUpdate(rgbox, "js");
	    }
	    
        updateAjax();        

	};
	
	editor.onDidChangeModelContent(editorUpdate);
	editor2.onDidChangeModelContent(editorUpdate2);
	editor3.onDidChangeModelContent(editorUpdate3);
	
	var goText = document.querySelector(".go small");
	var playMode = false;
    var replayCode = function ()
    {
        if (replayIndex >= tabRange.length) 
        {
            recordMode = true;
            playMode = false;
            goText.innerHTML = "press to play";
            return;
        }
        
        rgbox       = tabRange[replayIndex];
        curTab      = rgbox.tab;
        
        if ( (rgbox.tab == "html")|| (rgbox.tab == "css") || (rgbox.tab == "js") )
        { 
    	    updateTab();
            var id      = { major: 1, minor: 1 };
            var text    = rgbox.txt;
            var range   = { "startLineNumber" : rgbox.rg.sln, 
                            "endLineNumber" : rgbox.rg.eln, 
                            "startColumn" : rgbox.rg.sc, 
                            "endColumn" : rgbox.rg.ec };
                            
            //console.log(rgbox);
            var op      = {identifier: id, range: range, text: text, forceMoveMarkers: true};
        }
        if (rgbox.tab == "html") { 
            editor.executeEdits("my-source", [op]);
            editor.revealLineInCenter(range.endLineNumber);
        }
        if (rgbox.tab == "css") {
            editor2.executeEdits("my-source", [op]);
            editor2.revealLineInCenter(range.endLineNumber);
        }
        if (rgbox.tab == "js") {
            editor3.executeEdits("my-source", [op]);
            editor3.revealLineInCenter(range.endLineNumber);
        }
        
        updateAjax();        

        replayIndex++;

        updateStep();
        
        if (playMode)
        {
            replaySpeed = Math.max(10, parseInt(document.querySelector("input[name=speed]").value));
            setTimeout(replayCode, replaySpeed);
        }
    }
    
	var go = document.querySelector(".go");
	go.addEventListener("click", function() {
	    if (recordMode)
	    {
	        // REPLAY MODE
    	    recordMode = false;
            playMode = true;
            goText.innerHTML = "press to pause";
            // TEST JSON
            //var histoCode   = document.querySelector("textarea[name=history]");
            //jsonReplay      = JSON.stringify(tabRange, null, 1);
            //histoCode.value = jsonReplay;
            //tabRange        = JSON.parse(jsonReplay);
            if (!Array.isArray(tabRange)) {
                console.log("ERROR ON REPLAY" + jsonReplay);
                return;
            }
    
    	    htmlModel.setValue('');
    	    cssModel.setValue('');
    	    jsModel.setValue('');
    
    	    replayIndex = 0;
            replaySpeed = Math.max(10, parseInt(document.querySelector("input[name=speed]").value));
    
    	    setTimeout(replayCode, replaySpeed);
	    }
	    else if(playMode)
	    {
	        // PAUSE MODE
            goText.innerHTML = "press to play";
            playMode = false;
 	    }
 	    else
 	    {
	        // PLAY MODE
            goText.innerHTML = "press to pause";
            playMode = true;
            replaySpeed = Math.max(10, parseInt(document.querySelector("input[name=speed]").value));
    	    setTimeout(replayCode, replaySpeed);
 	    }
	});
	var reset = document.querySelector(".reset");
	reset.addEventListener("click", function() {
	    recordMode  = false;
	    htmlModel.setValue('');
	    cssModel.setValue('');
	    jsModel.setValue('');
	    replayIndex = 0;
        tabRange    = [];

	    recordMode  = true;

        updateStep();
	});

    
    var codeH = document.querySelector(".codeH");
	codeH.addEventListener("click", function() {
	    curTab = "html";
	    updateTab();
	});
    var codeC = document.querySelector(".codeC");
	codeC.addEventListener("click", function() {
	    curTab = "css";
	    updateTab();
	});
    var codeJ = document.querySelector(".codeJ");
	codeJ.addEventListener("click", function() {
	    curTab = "js";
	    updateTab();
	});

    var histo = document.querySelector(".history");
	histo.addEventListener("click", function() {
        // TEST JSON
        var tabHisto    = tabRange.slice(0);
        var sav     = {};
        sav.tab     = "meta";
        sav.mod     = "all";
	    //console.log(rgbox);
        sav.nb      = tabHisto.length;   // FOR POST-PROD
        sav.html    = htmlModel.getValue();
        sav.css     = cssModel.getValue();
        sav.js      = jsModel.getValue();
        tabHisto.push(sav);
        jsonReplay      = JSON.stringify(tabHisto, null, 1);
        var histoCode   = document.querySelector("textarea[name=history]");
        histoCode.value = jsonReplay;

        var lightbox = document.querySelector(".lightbox");
		lightbox.classList.add("show");
	});

    var close = document.querySelector(".lightbox .closeLB");
	close.addEventListener("click", function() {
        var lightbox = document.querySelector(".lightbox");
		lightbox.classList.remove("show");
	});
    var replace = document.querySelector(".lightbox .replaceHistory");
	replace.addEventListener("click", updateHistory);

    // init history if code loaded
    updateHistory();
    
    // resize
    window.addEventListener('resize', function(){
       editor.layout(); 
       editor2.layout(); 
       editor3.layout(); 
    });
});


var updateStep = function ()
{
    var tabStep = document.querySelectorAll(".step");
    for(step of tabStep) {
        step.innerHTML = replayIndex + " / " + tabRange.length;
    }
    
}
var updateHistory = function() {
    var histoCode   = document.querySelector("textarea[name=history]");
    jsonReplay      = histoCode.value.trim();
    if (jsonReplay != "")
    {
        var tabRange2   = JSON.parse(jsonReplay);
        if (Array.isArray(tabRange2)) {
            replayIndex = 0;
            tabRange    = tabRange2;
            
            updateStep();
        }
        else {
            console.log("ERROR ON REPLACE" + jsonReplay);
            return;
        }
    }
}


var updateTab = function ()
{
    var codeH = document.querySelector(".codeH");
    var codeC = document.querySelector(".codeC");
    var codeJ = document.querySelector(".codeJ");
    codeH.classList.remove("actif");    
    codeJ.classList.remove("actif");    
    codeC.classList.remove("actif");
    if (curTab == "html") codeH.classList.add("actif"); 
    if (curTab == "css") codeC.classList.add("actif"); 
    if (curTab == "js") codeJ.classList.add("actif"); 
    
}
    </script>
</section>

	</body>
</html>
