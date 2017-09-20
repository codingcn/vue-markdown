<template>
    <div>
        <form action="http://localhost/php_server_demo/form.php" method="post">
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

<script>
  import editor from './Editor'

  export default {
    name: 'app',
    components: {
      editor
    },
    data: function () {
      return {
        markdown: '```\n' +
        '<?php\n' +
        '    echo \'hello world.\';\n' +
        '?>\n' +
        '```',   // 默认填充的markdown数据
        upload_uri: 'http://localhost/php_server_demo/upload.php'   // 图片上传服务器地址
      }
    }
  }
</script>