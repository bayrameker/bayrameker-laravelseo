# Laravel SEO

**Özellikler**:
- PHP'den SEO etiketleri ayarlama
- Blade'den SEO etiketlerini ayarlama
- Otomatik olarak kapak resimleri oluşturmak için [Flipp](https://useflipp.com) ile entegrasyon
- Özel uzantı desteği
- Etkileyici ve basit API
- Özelleştirilebilir görünümler

Örnek Kullanım:
```php
seo()
    ->title($post->title)
    ->description($post->excerpt)
    ->twitter()
    ->flipp('blog')

```

## Kurulum

```sh
composer require bayrameker/laravelseo
```

Blade dosyanızın `<head>` etiketine aşağıdaki satırı ekleyin:

```html
<x-seo::meta />
```

## Kullanım

Paket herhangi bir PHP kodundan veya özellikle `@seo` yönergesi kullanılarak Blade'den kullanılabilir.

### PHP

Aşağıdaki yöntemleri çağırabileceğiniz SeoManager örneğini almak için `seo()` yardımcısını kullanın:

Mevcut yöntemler:
```js
site(string $site)
url(string $url)
title(string $title)
description(string $description)
image(string $url)

twitterCreator(string $username)
twitterSite(string $username)
twitterTitle(string $title)
twitterDescription(string $description)
twitterImage(string $url)
```

Örnek Kullanım:

```php
seo()->title('foo')->description('bar')
```

### Blade Dosyası

Yöntemleri Blade'den çağırmak için `@seo` yönergesini kullanabilirsiniz:

```html
@seo('title') // title değerini kullan
@seo('title', 'foo') // title değer tanımla ve kullan
@seo(['title' => 'foo']) // Başlığı önceden tanımla
```


### Twitter


```php
seo()->twitter();
```

```php
seo()->twitterTitle('About us')
```

### Favicon

Varsayılan olarak, hiçbir favicon bağlantısı eklenmez. Kendiniz şu şekilde kullanmalısınız : 

```php
seo()->favicon();
```

## Favicon Oluştur

favicon oluşturma kodu : 

```
php artisan seo:generate-favicons public/path-to/logo.png
```

Artisan konsolundan herhangi bir yol argümanı verilmezse, 'public/assets/logo.png' konumuna geri döneriz.

32x32 piksellik bir "public/favicon.ico" & "public/favicon.png" simgesi oluşturacağız. Çoğu durumda bu yeterli olacaktır.

**Lütfen [imagick](https://pecl.php.net/package/imagick) php uzantısını ve [intervention/image](http://image.intervention.io/) yüklemeniz gerektiğini unutmayın.**

### Varsayılanlar

Varsayılan değerleri yapılandırmak için, yöntemleri "default" bağımsız değişkenle çağırın:
```php
seo()
    ->title(default: 'Bayram Eker — Kişisel Website')
    ->description(default: 'Biz yazılım geliştiricisiyiz ...');
```

### Ekstra Taglar

Daha fazla etiket eklemek için "tag()" ve "rawTag()" yöntemlerini kullanabilirsiniz:

```php
seo()->tag('fb:image', asset('foo'));
seo()->rawTag('<meta property="fb:url" content="bar" />');
seo()->rawTag('fb_url', '<meta property="fb:url" content="bar" />'); 
```

### Standart URL

"og:url" ve kurallı URL "bağlantı" etiketlerini etkinleştirmek için şunu arayın:

```php
seo()->withUrl();
```
Bu, paketin `request()->url()` (sorgu dizesi *olmadan* geçerli URL) öğesinden okunmasını sağlar.

URL'yi değiştirmek isterseniz, `seo()->url()` kullanın:

```php
seo()->url(route('products.show', $this->product));
```

### Değer Değiştirme

Şablona eklenmeden önce belirli değerleri değiştirmek isteyebilirsiniz. Örneğin, `<title>` meta sonuna `| ile ek başlık eklemek isteyebilirsiniz. ` 

Bunu yapmak için, aşağıdaki gibi yöntem çağrılarına 'modify' argümanını eklemeniz yeterlidir:

```php
seo()->title(modify: fn (string $title) => $title . ' | Bayram Eker');
```

You can, of course, combine these with the defaults:

```php
seo()->title(
    default: 'BayramEker — Web Geliştirici',
    modify: fn (string $title) => $title . ' | Laravel Developer'
);
```


### Flipp entegrasyonu

İlk olarak, Flipp API anahtarlarınızı eklemeniz gerekir:
1. API anahtarınızı "FLIPP_KEY" ortam değişkenine ekleyin. Anahtarı [buradan]->(https://useflipp.com/settings/profile/api) alabilirsiniz..

2.  `config/services.php` gidin ve bunu ekleyin:
    ```php
    'flipp' => [
        'key' => env('FLIPP_KEY'),
    ],
    ```

Şablonları `AppServiceProvider` içerisine ekleyin:
```php
seo()->flipp('blog', 'v8ywdwho3bso');
seo()->flipp('page', 'egssigeabtm7');
```

Bundan sonra, aşağıdaki gibi `seo()->flipp()` çağırarak şablonları kullanabilirsiniz:
```php
seo()->flipp('blog', ['title' => 'Foo', 'content' => 'bar'])`
```

Hiçbir veri sağlanmazsa, yöntem mevcut SEO yapılandırmasındaki "başlık" ve "açıklama"yı kullanır:

```php
seo()->title($post->title);
seo()->description($post->excerpt);
seo()->flipp('blog');
```

'flipp()' yöntemi ayrıca, blog kapak resimleri gibi başka yerlerde kullanmanıza izin veren, resme endeksli bir URL döndürür.

```php
<img alt="@seo('title')" src="@seo('flipp', 'blog')">
```

## Örnekler

### Service Provider

Services Provider `boot()` yöntemindeki varsayılan yapılandırma:

```php
seo()
    ->site('Bayram Eker — Laravel Developer')
    ->title(
        default: 'BayramEker — Biz yazılım geliştiricisiyiz',
        modify: fn (string $title) => $title . ' | Laravel Developer'
    )
    ->description(default: 'yazılım geliştiricisiyiz ...')
    ->image(default: fn () => asset('header.png'))
    ->flipp('blog', 'o1vhcg5npgfu')
    ->twitterSite('archtechx');
```

### Controller

Controller SEO meta verilerini yapılandırma örneği.

```php
public function show(Post $post)
{
    seo()
        ->title($post->title)
        ->description(Str::limit($post->content, 50))
        ->flipp('blog', ['title' => $page->title, 'content' => $page->excerpt]);

    return view('blog.show', compact($post));
}
```

### View

Bu örnek, View'e iletilen değerleri kullanarak genel SEO yapılandırmasını ayarlayan bir Blade View kullanır.

```html
@seo(['title' => $page->name])
@seo(['description' => $page->excerpt])
@seo(['flipp' => 'content'])

<h1>{{ $page->title }}</h1>
<p>{{ $page->excerpt }}</p>

<p class="prose">
    {{ $page->body }}
</p>
```

## Özelleştirme

Bu paket tamamen açıktır ve görünümleri değiştirilerek (mevcut şablonları değiştirmek için) veya bir uzantı geliştirerek (daha fazla şablon eklemek için) özelleştirilebilir.

### Görüntüleme

`php artisan satıcısı:yayın --tag=seo-views` komutunu çalıştırarak Blade View'lerini yayınlayabilirsiniz.

### Uzantılar

Özel bir uzantı kullanmak için, istenen meta etiketlerle bir Blade *component* oluşturun. Component, `{{ seo()->get('foo') }}` veya `@seo('foo')` kullanarak verileri okumalıdır.

Örneğin:

```php
<meta name="facebook-title" content="@seo('facebook.foo')">
```

View oluşturulduktan sonra uzantıyı kaydedin:

```php
seo()->extension('facebook', view: 'my-component');
//  <x-my-component>
```

Bir uzantı için veri ayarlamak için (bizim durumumuzda `facebook`), çağrıları camelCase'de uzantı adıyla önekleyin veya `->set()` yöntemini kullanın:

```php
seo()->facebookFoo('bar');
seo()->facebookTitle('Hakkımızda');
seo()->set('facebook.description', 'Biz bir web geliştirme ...');
seo(['facebook.description' => 'Biz bir web geliştirme ...']);
```

Bir uzantıyı devre dışı bırakmak için, "extension()" çağrısındaki ikinci argümanı false olarak ayarlayın:

```php
seo()->uzantı('facebook', false);
```

## Gelişim

Tüm kontrolleri yerel olarak çalıştırın:

```sh
./check
```

Kod stili, php-cs-fixer tarafından otomatik olarak düzeltilecektir.
