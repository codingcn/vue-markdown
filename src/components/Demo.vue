<template>
    <div>
        <form action="http://localhost/vue-md/php_server_demo/form.php" method="post">
            <editor v-bind:markdown="form.markdown_md"
                    v-bind:upload_action_editor="upload_action_editor"
                    v-on:getEditorContent="getEditorContent"
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
                form: {
                    content_html: '',
                    markdown_md: '```\n' +
                    '<?php\n' +
                    '    echo \'hello world.\';\n' +
                    '?>\n' +
                    '```',   // 默认填充的markdown数据
                },
                upload_action_editor: 'http://localhost/vue-md/php_server_demo/upload.php'   // 图片上传服务器地址
            }
        },
        methods: {
            getEditorContent(data) {
                this.form.content_md = data.content_md
                this.form.content_html = data.content_html
            },
        }
    }
</script>