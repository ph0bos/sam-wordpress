/*!
 * SAM Connect
 * http://www.samdesk.io
 * V1.0
 *
 * Copyright 2013, Social Asset Management Ltd.
 *
 * Date: 28/08/2013
 */
 (function() {
        tinymce.create('tinymce.plugins.samConnect', {
                init : function(ed, url) {
                        var t = this;
                        t.url = url;
                        t.editor = ed;
                        ed.onBeforeSetContent.add(function(ed, o) {
                                o.content = t._do_samassets(o.content);
                        });
                        ed.onPostProcess.add(function(ed, o) {
                                if (o.get)
                                        o.content = t._get_samassets(o.content);
                        });
                        ed.onInit.add(function(ed) {
                                // iOS6 doesn't show the buttons properly on click, show them on 'touchstart'
                                if ( 'ontouchstart' in window ) {
                                        ed.dom.events.add(ed.getBody(), 'touchstart', function(e){
                                                var target = e.target;
                                                if ( target.nodeName == 'IMG' && ed.dom.hasClass(target, 'samAssetVisAid') ) {
                                                        ed.selection.select(target);
                                                        ed.dom.events.cancel(e);
                                                        ed.plugins.wordpress._hideButtons();
                                                        ed.plugins.wordpress._showButtons(target, 'wp_samconnectbtns');
                                                }
                                        });
                                }
                        });
                        ed.onMouseDown.add(function(ed, e) {
                                if ( e.target.nodeName == 'IMG' && ed.dom.hasClass(e.target, 'samAssetVisAid') ) {
                                        ed.plugins.wordpress._hideButtons();
                                        ed.plugins.wordpress._showButtons(e.target, 'wp_samconnectbtns');
                                }
                        });
                },
                _do_samassets : function(co) {
                        return co.replace(/\[SAMASSET([^\]]*)\]/g, function(a,b){
                                var socialtype;
                                var split = b.split(' ');
                                for (var i = 0; i < split.length; i++) {
                                        var s = split[i].split('=');
                                        if (s[0] === "socialtype") {
                                                socialtype = s[1].slice(1, -1);
                                        }
                                }
				return '<img src="' + window.SAMCU + '/i/wp' + socialtype + '.png" class="samAssetVisAid" title="SAMASSET'+tinymce.DOM.encode(b)+'" />';
				
                        });
                },
                _get_samassets : function(co) {
                        function getAttr(s, n) {
                                n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
                                return n ? tinymce.DOM.decode(n[1]) : '';
                        };
                        return co.replace(/(?:<p[^>]*>)*(<img[^>]+>)(?:<\/p>)*/g, function(a,im) {
                                var cls = getAttr(im, 'class');
                                if ( cls.indexOf('samAssetVisAid') != -1 )
                                        return '<p>['+tinymce.trim(getAttr(im, 'title'))+']</p>';
                                return a;
                        });
                },
                getInfo : function() {
                        return {
                                longname : 'SAM Connect',
                                author : 'SAM',
                                authorurl : 'http://samdesk.io',
                                infourl : 'http://samdesk.io',
                                version : "1.0"
                        };
                }
        });
        tinymce.PluginManager.add('samconnect', tinymce.plugins.samConnect);
})();
