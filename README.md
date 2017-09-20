# vue-md

>因为作者受不了已存在的那些臃肿的`markdown`编辑器，所以自己又造了一个轮子，easy enough!

## 功能与特性
* 基于webpack构建，单组件开发，方便再次开发扩展，开箱即用。
* 简洁不臃肿，只实现了一些的功能。
* 支持所有标准markdown语法/代码高亮/表格插入等。
* 图片支持拖拽、截图粘贴两种上传方式,支持实时预览、（跨域）上传。
* 支持全屏编辑和分栏预览两种模式。

_注：关于一些个性化功能，比如 ***工具栏*** 和 ***TOC目录*** 。其实只要熟悉了`markdown`语法之后，是没有人会通过快捷工具栏来写`markdown`内容的，`TOC目录`的话我尝试写了一下，实现也很简单，就是在读取`markdown`编译后的`html`内容的时候，从中取下`h2 h3 h4...`几个节点而已。同学们如果需要的话，可以自己尝试着去做一下，不懂的地方欢迎交流。_

## 使用方法
***图片上传（后端）***
作者提供了PHP的服务器端代码，请自行参考修改，代码位置`/php_server_demo`

***vue模板***
```
<template>
    <div>
        <form action="http://localhost/blog/public/api/test" method="post">
            <editor v-bind:markdown="markdown"
                    v-bind:upload_uri="upload_uri"
            >
            </editor>
            <div style="margin: 1rem auto; width: 85%;">
                <button>提交</button>
            </div>
        </form>
    </div>
</template>
```
***js内容***
```
<script>
  import editor from './Editor'

  export default {
    name: 'app',
    components: {
      editor
    },
    data: function () {
      return {
        markdown: '',   // 默认填充的markdown数据，编辑文章的时候往这填数据
        upload_uri: 'http://localhost/api/upload/uploadimage'   // 指定图片上传服务器地址
      }
    }
  }
</script>
```

## Build Setup

``` bash
# install dependencies
cnpm install

# serve with hot reload at localhost:8080
npm run dev

# build for production with minification
npm run build

# build for production and view the bundle analyzer report
npm run build --report

```
## 依赖于以下项目
`axios`
`github-markdown-css`
`highlight.js`
`marked`
`promise-polyfill`
`vue`

