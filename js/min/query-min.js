var Query={init:function(){},value:function(e){var n=e.replace(/[\[]/,"\\[").replace(/[\]]/,"\\]"),c="[\\?&]"+n+"=([^&#]*)",r=new RegExp(c),i=r.exec(window.location.search);return null===i?"":decodeURIComponent(i[1].replace(/\+/g," "))}};$(function(){Query.init()});