<template>
  <section class="editor-container">
    <div class="editor-toolbar">
      <ul class="editor-menu">
        <li class="">
          <span v-on:click="changeMode" v-text="labels"></span>
        </li>
      </ul>
    </div>
    <div class="editor-body">
      <textarea id="editor_md"
                name="content_md"
                v-on:paste="pasteFile"
                :value="content_md"
                :class="{
                  'edit': mode === 'edit',
                  'preview': mode === 'preview',
                }"
                @input="update"
                @drop="drop($event)"
                @dragenter="dragenter($event)"
                @dragover="dragover($event)">
      </textarea>
      <div id="editor_preview"
           class="markdown-body"
           v-html="compiledMarkdown"
           :class="{
              'edit': mode === 'edit',
              'preview': mode === 'preview'
            }">

      </div>
      <textarea name="content_html" :value="content_html" style="display: none"></textarea>
    </div>
  </section>
</template>

<script>
  require('../../node_modules/github-markdown-css/github-markdown.css')
  require('../../node_modules/highlight.js/styles/github.css')

  import marked from 'marked'
  import Promise from 'promise-polyfill'
  // 为了让IE支持 axios
  if (!window.Promise) {
    window.Promise = Promise
  }
  import axios from 'axios'
  //  import qs from 'qs'

  export default {
    props: ['markdown', 'upload_uri'],
    name: 'editor',
    beforeMount: function () {
      this.setMarkdown()
    },
    data: function () {
      return {
        content_md: this.markdown,
        content_html: this.content_html,
        labels: '编辑',
        mode: 'preview'
      }
    },
    computed: {
      compiledMarkdown: function () {
        // 编译markdown为html
        this.content_html = marked(this.content_md, {sanitize: true})
        return this.content_html
      }
    },
    methods: {
      changeMode: function () {
        // 切换模式
        this.mode = this.mode === 'preview' ? 'edit' : 'preview'
        this.labels = this.mode === 'edit' ? '预览' : '编辑'
      },
      /**
       * 设置marked参数
       */
      setMarkdown: function () {
        let renderer = new marked.Renderer()
        marked.setOptions({
          renderer: renderer,
          gfm: true,
          tables: true,
          breaks: false,
          pedantic: false,
          sanitize: true,
          smartLists: true,
          smartypants: false,
          highlight: function (code) {
            return require('highlight.js').highlightAuto(code).value
          }
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
        let formdata = new FormData()
        formdata.append('image', file)
        axios({
          url: this.upload_uri,
          method: 'post',
          data: formdata,
          headers: {'Content-Type': 'application/multipart/form-data'}
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

<!-- Add "scoped" attribute to limit CSS to this component only -->

<style scoped>

  .editor-container {
    margin: 0 auto;
    width: 85%;
    height: 30rem;
  }

  .editor-menu {
    margin: 0;
    padding: 0;
    height: 2.5rem;
    line-height: 2.5rem;
    background-color: #eee;
  }

  .editor-body {
    border: 0.1rem solid #eee;
    height: 27.3rem;
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
    padding: 1rem;
  }

  #editor_preview.preview {
    margin-left: 0;
  }

  #editor_md.preview {
    border-right: 0.15rem solid #ccc;
    background: #f6f6f6;
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
