root        /var/www/lc/site/web;

rewrite     ^/(app|app_dev)\.php/?(.*)$ /$1 permanent;

{% if env.SYMFONY_ENV == 'dev' %}
location / {
    index       app_dev.php;
    try_files   $uri @rewriteapp;
}


location @rewriteapp {
    rewrite     ^(.*)$ /app_dev.php/$1 last;
}
{% else %}
location / {
    index       app.php;
    try_files   $uri @rewriteapp;
}


location @rewriteapp {
    rewrite     ^(.*)$ /app.php/$1 last;
}
{% endif %}

location ~ ^/(app|app_dev|config)\.php(/|$) {
    fastcgi_pass            php{{ php_version }};
    fastcgi_buffer_size     16k;
    fastcgi_buffers         4 16k;
    fastcgi_split_path_info ^(.+\.php)(/.*)$;
    include                 fastcgi_params;
    fastcgi_param           SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
