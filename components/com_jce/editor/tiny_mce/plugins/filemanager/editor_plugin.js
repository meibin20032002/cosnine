/* jce - 2.6.24 | 2018-01-24 | http://www.joomlacontenteditor.net | Copyright (C) 2006 - 2018 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html */
!function(){var openwith={googledocs:{supported:["doc","docx","xls","xlsx","ppt","pptx","pdf","pages","ai","psd","tiff","dxf","svg","ps","ttf","xps","rar"],link:"https://docs.google.com/viewer?url=",embed:"https://docs.google.com/viewer?embedded=true&url="},officeapps:{supported:["doc","docx","xls","xlsx","ppt","pptx"],link:"https://view.officeapps.live.com/op/view.aspx?src=",embed:"https://view.officeapps.live.com/op/embed.aspx?src="}};tinymce.create("tinymce.plugins.FileManager",{init:function(ed,url){function isFile(n){return n&&ed.dom.is(n,".jce_file, .wf_file, .mce-item-iframe")}ed.addCommand("mceFileManager",function(){ed.selection.getNode();ed.windowManager.open({file:ed.getParam("site_url")+"index.php?option=com_jce&view=editor&plugin=filemanager",width:780+ed.getLang("filemanager.delta_width",0),height:680+ed.getLang("filemanager.delta_height",0),inline:1,popup_css:!1},{plugin_url:url})}),this.editor=ed,this.url=url,ed.addButton("filemanager",{title:"filemanager.desc",cmd:"mceFileManager",image:url+"/img/filemanager.png"}),ed.onNodeChange.add(function(ed,cm,n,co){"IMG"!==n.nodeName&&"SPAN"!==n.nodeName||(n=ed.dom.getParent(n,"A")),cm.setActive("filemanager",co&&isFile(n)),n&&isFile(n)&&cm.setActive("filemanager",!0)}),ed.onInit.add(function(ed){ed.settings.compress.css||ed.dom.loadCSS(url+"/css/content.css"),ed&&ed.plugins.contextmenu&&ed.plugins.contextmenu.onContextMenu.add(function(th,m,e){m.add({title:"filemanager.desc",icon_src:url+"/img/filemanager.png",cmd:"mceFileManager"})})})},insertUploadedFile:function(o){var ed=this.editor,data=this.getUploadConfig();if(data&&data.filetypes&&new RegExp(".("+data.filetypes.join("|")+")$","i").test(o.file)){var args={href:o.file,title:o.title||o.name},html="",method=o.method||"link";if(o.openwith){var config=openwith[o.openwith];args.href=encodeURIComponent(decodeURIComponent(ed.documentBaseURI.toAbsolute(args.href,ed.settings.remove_script_host))),new RegExp(".("+config.supported.join("|")+")$","i").test(o.file)&&(args.href=config[method]+args.href)}if("embed"===method){var w=o.width||"100%",h=o.height||"100%";return ed.dom.create("img",{alt:o.name,width:w,height:h,src:"data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7","data-mce-json":'{"iframe":{"src" : "'+args.href+'", "seamless" : "seamless"}}',class:"mce-item-iframe"})}o.features?tinymce.each(o.features,function(n){html+=ed.dom.createHTML(n.node,n.attribs||{},n.html||"")}):html=o.name;var cls=["wf_file"],attribs=["target","id","dir","class","charset","style","hreflang","lang","type","rev","rel","tabindex","accesskey"];return o.style&&(args.style=ed.dom.parseStyle(o.style),delete o.style),tinymce.each(attribs,function(k){"undefined"!=typeof o[k]&&("class"==k?cls.push(o[k]):args[k]=o[k])}),args.class=cls.join(" "),ed.dom.create("a",args,html)}return!1},getUploadURL:function(file){var data=(this.editor,this.getUploadConfig());if(data&&data.filetypes){if(/\.(jpg|jpeg|png|tiff|bmp|gif)$/i.test(file.name)&&(tinymce.plugins.imgmanager||tinymce.plugins.imgmanager_ext))return!1;if(new RegExp(".("+data.filetypes.join("|")+")$","i").test(file.name))return this.editor.getParam("site_url")+"index.php?option=com_jce&view=editor&plugin=filemanager"}return!1},getUploadConfig:function(){var ed=this.editor,data=ed.getParam("filemanager_upload");return data}}),tinymce.PluginManager.add("filemanager",tinymce.plugins.FileManager)}();