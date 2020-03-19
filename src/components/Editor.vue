<template>

    <section class="editor-container"
             :class="{
              'full': screen_mode === 'full',
              'general': screen_mode === 'general'
              }">
        <div class="editor-toolbar">
            <ul class="editor-menu">
                <li class="">
                    <span v-on:click="changeEditMode" v-text="edit_labels"></span>
                </li>
                <li class="">
                    <span v-on:click="changeScreenMode" v-text="screen_labels"></span>
                </li>
            </ul>
        </div>
        <div class="editor-body">
      <textarea id="editor_md"
                name="content_md"
                v-on:paste="pasteFile"
                :value="content_md"
                :class="{
                  'edit': edit_mode === 'edit',
                  'preview': edit_mode === 'preview',
                }"
                @input="update"
                @drop="drop($event)"
                @dragenter="dragenter($event)"
                @dragover="dragover($event)">
      </textarea>
            <div id="editor_preview"
                 class="markdown-body"
                 v-html="compiledMarkdown()"
                 :class="{
              'edit': edit_mode === 'edit',
              'preview': edit_mode === 'preview'
            }">
            </div>
            <textarea name="content_html" :value="content_html" style="display: none"></textarea>
        </div>
    </section>
</template>

<script>
    require('../../node_modules/github-markdown-css/github-markdown.css')
    // 高亮样式
    // require('../../node_modules/highlight.js/styles/ocean.css')
    import '../assets/oceanic-next-for-highlight-js.css';
    import highlightjs from 'highlight.js';

    import marked from 'marked'
    import Promise from 'promise-polyfill'
    import axios from 'axios'
    // 为了让IE支持 axios
    if (!window.Promise) {
        window.Promise = Promise
    }
    //  import qs from 'qs'

    export default {
        props: ['markdown', 'upload_action_editor'],
        name: 'editor',
        beforeMount: function () {
            this.setMarkdown()
        },
        watch: {
            markdown: function (val) {
                this.content_md = val
            }
        },
        data: function () {
            return {
                content_md: this.markdown,
                content_html: this.content_html,
                edit_labels: '编辑',
                edit_mode: 'preview',
                screen_labels: '全屏',
                screen_mode: 'general'
            }
        },
        computed: {},
        methods: {
            compiledMarkdown: function () {
                // 编译markdown为html

                let svg = `<div class="mac-window"><svg xmlns="http://www.w3.org/2000/svg" width="54" height="14" viewBox="0 0 54 14">
<g fill="none" fill-rule="evenodd" transform="translate(1 1)">
    <circle cx="6" cy="6" r="6" fill="#FF5F56" stroke="#E0443E" stroke-width=".5"></circle>
    <circle cx="26" cy="6" r="6" fill="#FFBD2E" stroke="#DEA123" stroke-width=".5"></circle>
    <circle cx="46" cy="6" r="6" fill="#27C93F" stroke="#1AAB29" stroke-width=".5"></circle></g></svg>
    <span class="copy">复制代码</span>
    </div>`
                let h = marked(this.content_md, {sanitize: false})
                h = h.toString().replace(/<pre>/g, "<pre>" + svg)
                this.content_html = h
                this.$emit('getEditorContent', {
                        'content_md': this.content_md,
                        'content_html': this.content_html
                    }
                )
                return this.content_html
            },
            changeEditMode: function () {
                // 切换模式
                this.edit_mode = this.edit_mode === 'preview' ? 'edit' : 'preview'
                this.edit_labels = this.edit_mode === 'edit' ? '预览' : '编辑'
            },
            changeScreenMode: function () {
                // 切换模式
                this.screen_mode = this.screen_mode === 'general' ? 'full' : 'general'
                this.screen_labels = this.screen_mode === 'full' ? '收缩' : '全屏'
            },
            /**
             * 设置marked参数
             */
            setMarkdown: function () {
                let renderer = new marked.Renderer()
                renderer.code = (code, language) => {
                    // Check whether the given language is valid for highlight.js.
                    const validLang = !!(language && highlightjs.getLanguage(language));
                    const codeClass = language ? 'hljs ' + language : '';
                    // Highlight only if the language is valid.
                    const highlighted = validLang ? highlightjs.highlight(language, code).value : highlightjs.highlightAuto(code).value;
                    // Render the highlighted code with `hljs` class.
                    return `<pre><code class="${codeClass}">${highlighted}</code></pre>`;
                };
                marked.setOptions({
                    renderer: renderer,
                    gfm: true,
                    tables: true,
                    breaks: false,
                    pedantic: false,
                    sanitize: true,
                    smartLists: true,
                    smartypants: false,
                    xhtml: false
                })
            },
            /**
             * 更新
             */
            update: function (e) {
                this.content_md = e.target.value
            },
            /**
             * 上传的文件列表
             */
            fileList: function (files) {
                for (let i = 0; i < files.length; i++) {
                    this.fileAdd(files[i])
                }
            },
            /**
             * 在光标之后插入数据
             * @param textDom
             * @param value
             */
            insertHtmlAtCaret: function (textDom, value) {
                let selectRange
                if (document.selection) {
                    // IE Support
                    textDom.focus()
                    selectRange = document.selection.createRange()
                    selectRange.text = value
                    textDom.focus()
                } else if (textDom.selectionStart || textDom.selectionStart === '0') {
                    // Firefox support
                    let startPos = textDom.selectionStart
                    let endPos = textDom.selectionEnd
                    let scrollTop = textDom.scrollTop
                    textDom.value = textDom.value.substring(0, startPos) + value + textDom.value.substring(endPos, textDom.value.length)
                    textDom.focus()
                    textDom.selectionStart = startPos + value.length
                    textDom.selectionEnd = startPos + value.length
                    textDom.scrollTop = scrollTop
                } else {
                    textDom.value += value
                    textDom.focus()
                }
            },

            /**
             * 粘贴上传
             */
            pasteFile: function (event) {
                if (event.clipboardData || event.originalEvent) {
                    // not for ie11 某些chrome版本使用的是event.originalEvent
                    let clipboardData = (event.clipboardData || event.originalEvent.clipboardData)
                    if (clipboardData.items) {
                        let items = clipboardData.items
                        let blob = null
                        // 在items里找粘贴的image,据上面分析,需要循环
                        for (let i = 0; i < items.length; i++) {
                            if (items[i].type.indexOf('image') === 0) {
                                blob = items[i].getAsFile()
                            } else {
                                blob = items[i]
                            }
                        }
                        // 如果不是普通字符串
                        if (blob.kind !== 'string') {
                            // 阻止默认行为即不让剪贴板内容在div中显示出来
                            event.preventDefault()
                            if (blob !== null) {
                                let reader = new FileReader()
                                reader.vue = this
                                reader.onload = function (event) {
                                    // event.target.result 即为图片的Base64编码字符串
                                    let base64Str = event.target.result
                                    // 可以在这里写上传逻辑 直接将base64编码的字符串上传（可以尝试传入blob对象，看看后台程序能否解析）
                                    this.vue.uploadFile(base64Str)
                                }
                                reader.readAsDataURL(blob)
                            }
                        }
                    }
                } else {
                    // for ie
                    console.log('boom！')
                }
            },
            /**
             * 添加文件
             * @param file
             */
            fileAdd: function (file) {
                // 判断是否为图片文件
                if (file.type.indexOf('image') === -1) {
                    alert('只能上传图片')
                } else {
                    let reader = new FileReader()
                    reader.vue = this
                    reader.readAsDataURL(file)
                    reader.onload = function () {
                        this.vue.uploadFile(this.result)
                    }
                }
            },
            /**
             * 上传图片
             * @param file base64数据
             */
            uploadFile: function (file) {
                let form_data = new FormData()
                form_data.append('image', file)
                axios({
                    url: this.upload_action_editor,
                    method: 'post',
                    data: form_data,
                    headers: {

                        'Content-Type': 'application/multipart/form-data'
                    }
                }).then((res) => {
                    // TODO 这儿做后台返回判断
                    let textDom = document.querySelector('#editor_md')
                    // TODO 注意后端返回的图片地址是相对路径的还是完整路径的
                    let imgPath = '![](' + res.data + ')'
                    this.insertHtmlAtCaret(textDom, imgPath)
                    // 更新预览视图
                    this.content_md = textDom.value
                })
            },
            dragenter: function (el) {
                el.stopPropagation()
                el.preventDefault()
            },
            dragover: function (el) {
                el.stopPropagation()
                el.preventDefault()
            },
            drop: function (el) {
                el.stopPropagation()
                el.preventDefault()
                this.fileList(el.dataTransfer.files)
            }
        }
    }
</script>

<style>
    /*重新定义code块背景色*/
    .markdown-body .highlight pre, .markdown-body pre {
        background-color: #2F4148;
        border-radius: .42rem;
        margin: 2rem 1rem;
        outline: 1.3rem solid #F3F4F4;
    }

    /*重写github-markdown.css 适配highlight.js黑色主题*/
    .markdown-body pre code {
        color: #ffffff;
        height: auto;
        min-width: inherit;
    }
    .mac-window{
        margin-bottom: .6rem;
        line-height: 1rem;
        height: 1rem;
        display: flex;
        justify-content: space-between;
    }
    .mac-window svg{
        float: left;
    }
    .mac-window .copy{
        vertical-align: bottom;
        color: gray;
        float: right;

    }
    /*svg {*/
    /*    margin-top: -24px;*/
    /*    position: relative;*/
    /*    top: 34px;*/
    /*    margin-left: 14px;*/
    /*    z-index: 2;*/
    /*}*/
</style>
<!-- Add "scoped" attribute to limit CSS to this component only -->

<style scoped>
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px
    }

    ::-webkit-scrollbar-thumb {
        border-radius: 3px;
        -moz-border-radius: 3px;
        -webkit-border-radius: 3px;
        background-color: #c3c3c3
    }

    ::-webkit-scrollbar-track {
        background-color: transparent
    }

    .editor-container.general {
        margin: 0 auto;
        width: 100%;
        height: 70%;
    }

    .editor-container.full {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        min-height: 100%; /*或 100%*/
        z-index: 1000;
    }

    .editor-container.full .editor-toolbar {
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 10000;
    }

    .editor-menu {
        margin: 0;
        padding: 0;
        height: 2.5rem;
        line-height: 2.5rem;
        background-color: #eee;
    }

    .editor-container.general .editor-body {
        border: 0.1rem solid #eee;
        height: 27.3rem;
    }

    .editor-container.full .editor-body {
        margin-top: 2.5rem;
        margin-bottom: 4rem;
        border: 0.1rem solid #eee;
        height: 100%;
        background-color: #fcfaf2;
    }

    .editor-menu li {
        float: right;
        list-style-type: none;
    }

    .editor-menu li span {
        font-size: 0.8rem;
        display: inline-block;
        height: 2.5rem;
        line-height: 2.5rem;
        padding: 0 1rem;
        color: #555;
        cursor: pointer;
    }

    .editor-menu li span:hover {
        color: #444;
        background-color: #ddd;
    }

    #editor_md, #editor_preview {
        float: left;
        vertical-align: top;
        box-sizing: border-box;
        height: 100%;
        width: 50%;
        overflow: auto;
        display: block;
    }

    .editor-container.general #editor_md,
    .editor-container.general #editor_preview {
        padding: 1rem 1rem 2.5rem 1rem;
    }

    .editor-container.full #editor_md,
    .editor-container.full #editor_preview {
        padding: 1rem 1rem 5rem 1rem;
    }

    #editor_preview.preview {
        margin-left: 0;
    }

    #editor_md.preview {
        border-right: 0.15rem solid #ccc;
    }

    #editor_md.edit {
        width: 100%;
    }

    #editor_preview.edit {
        display: none;
    }

    #editor_md {
        color: #333;
        border: none;
        resize: none;
        outline: none;
        line-height: 1.4rem;
        font-family: 'Monaco', courier, monospace;
    }
</style>
