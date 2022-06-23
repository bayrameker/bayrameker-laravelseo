<?php

declare(strict_types=1);

namespace Bayram\SEO;

use Closure;
use Illuminate\Support\Str;

/**
 * @method $this title(string $title = null, ...$args) Set the title.
 * @method $this description(string $description = null, ...$args) Set the description.
 * @method $this url(string $url = null, ...$args) Set the canonical URL.
 * @method $this site(string $site = null, ...$args) Set the site name.
 * @method $this image(string $url = null, ...$args) Set the cover image.
 * @method $this type(string $type = null, ...$args) Set the page type.
 * @method $this twitter(enabled $bool = true, ...$args) Enable the Twitter extension.
 * @method $this twitterCreator(string $username = null, ...$args) Set the Twitter author.
 * @method $this twitterSite(string $username = null, ...$args) Set the Twitter author.
 * @method $this twitterTitle(string $title = null, ...$args) Set the Twitter title.
 * @method $this twitterDescription(string $description = null, ...$args) Set the Twitter description.
 * @method $this twitterImage(string $url = null, ...$args) Set the Twitter cover image.
 */
class SEOManager
{
    /** Value modifiers. */
    protected array $modifiers = [];

    /** Default values. */
    protected array $defaults = [];

    /** User-configured values. */
    protected array $values = [];

    /** List of extensions. */
    protected array $extensions = [
        'twitter' => false,
    ];

   /** Ek özellikler için meta veriler. */
    protected array $meta = [];

  /** Ekstra başlık etiketleri. */
    protected array $tags = [];

   /** Kullanılan tüm değerleri al. */
    public function all(): array
    {
        return collect($this->getKeys())
            ->mapWithKeys(fn (string $key) => [$key => $this->get($key)])
            ->toArray();
    }

/** Kullanılan anahtarların bir listesini alın. */
    protected function getKeys(): array
    {
        return collect([
                'site', 'title', 'image', 'description', 'url', 'type',
                'twitter.creator', 'twitter.site', 'twitter.title', 'twitter.image', 'twitter.description',
            ])
            ->merge(array_keys($this->defaults))
            ->merge(array_keys($this->values))
            ->unique()
            ->filter(function (string $key) {
                if (count($parts = explode('.', $key)) > 1) {
                    if (isset($this->extensions[$parts[0]])) {
                        // Is the extension allowed?
                        return $this->extensions[$parts[0]];
                    }

                    return false;
                }

                return true;
            })
            ->toArray();
    }

/** Değiştirilmiş bir değer alın. */
    protected function modify(string $key): string|null
    {
        return isset($this->modifiers[$key])
            ? $this->modifiers[$key](value($this->values[$key]))
            : value($this->values[$key]);
    }

    /**
     * Set one or more values.
     *
     * @param string|array<string, string> $key
     */
    public function set(string|array $key, string|Closure|null $value = null): string|array|null
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }

            return collect($key)
                ->keys()
                ->mapWithKeys(fn (string $key) => [$key => $this->get($key)])
                ->toArray();
        }

        $this->values[$key] = $value;

        if (Str::contains($key, '.')) {
            $this->extension(Str::before($key, '.'), enabled: true);
        }

        return $this->get($key);
    }

/** Bir değeri çöz. */
    public function get(string $key): string|null
    {
        return isset($this->values[$key])
            ? $this->modify($key)
            : value($this->defaults[$key] ?? (
                Str::contains($key, '.') ? $this->get(Str::after($key, '.')) : null
            ));
    }

/** Değişiklik yapılmadan bir değer alın. */
    public function raw(string $key): string|null
    {
        return isset($this->values[$key])
            ? value($this->values[$key])
            : value($this->defaults[$key] ?? (
                Str::contains($key, '.') ? $this->get(Str::after($key, '.')) : null
            ));
    }

/** Bir uzantı yapılandırın. */
    public function extension(string $name, bool $enabled = true, string $view = null): static
    {
        $this->extensions[$name] = $enabled;

        if ($view) {
            $this->meta("extensions.$name.view", $view);
        }

        return $this;
    }

/** Etkinleştirilmiş uzantıların bir listesini alın. */
    public function extensions(): array
    {
        return collect($this->extensions)
            ->filter(fn (bool $enabled) => $enabled)
            ->keys()
            ->mapWithKeys(fn (string $extension) => [
                $extension => $this->meta("extensions.$extension.view") ?? ('seo::extensions.' . $extension),
            ])
            ->toArray();
    }

/** Flipp'i yapılandırın veya kullanın. */
    public function flipp(string $alias, string|array $data = null): string|static
    {
        if (is_string($data)) {
            $this->meta("flipp.templates.$alias", $data);

            return $this;
        }

        if ($data === null) {
            $data = [
                'title' => $this->raw('title'),
                'description' => $this->raw('description'),
            ];
        }

        $query = base64_encode(json_encode($data, JSON_THROW_ON_ERROR));

        /** @var string $template */
        $template = $this->meta("flipp.templates.$alias");

        $signature = hash_hmac('sha256', $template . $query, config('services.flipp.key'));

        return $this->set('image', "https://s.useflipp.com/{$template}.png?s={$signature}&v={$query}");
    }

/** Favicon uzantısını etkinleştir. */
    public function favicon(): static
    {
        $this->extensions['favicon'] = true;

        return $this;
    }

/** Belge başlığına kurallı URL etiketleri ekleyin. */
    public function withUrl(): static
    {
        $this->url(request()->url());

        return $this;
    }

/** Fazladan tüm head etiketlerini al. */
    public function tags(): array
    {
        return $this->tags;
    }

/** Belirli bir etiket ayarlandı mı? */
    public function hasRawTag(string $key): bool
    {
        return isset($this->tags[$key]) && ($this->tags[$key] !== null);
    }

/** Belirli bir meta etiket ayarlanmış mı? */
    public function hasTag(string $property): bool
    {
        return $this->hasRawTag("meta.{$property}");
    }

/** Bir başlık etiketi ekleyin. */
    public function rawTag(string $key, string $tag = null): static
    {
        $tag ??= $key;

        $this->tags[$key] = $tag;

        return $this;
    }

   /** Bir meta etiket ekleyin. */
    public function tag(string $property, string $content): static
    {
        $this->rawTag("meta.{$property}", "<meta property=\"{$property}\" content=\"{$content}\" />");

        return $this;
    }

    
    public function meta(string|array $key, string|array $value = null): mixed
    {
        if (is_array($key)) {
            /** @var array<string, string> $key */
            foreach ($key as $k => $v) {
                $this->meta($k, $v);
            }

            return $this;
        }

        if ($value === null) {
            return data_get($this->meta, $key);
        }

        data_set($this->meta, $key, $value);

        return $this;
    }

/**  yöntem çağrılarını yönet. */
    public function __call(string $name, array $arguments): string|array|null|static
    {
        if (isset($this->extensions[$name])) {
            return $this->extension($name, $arguments[0] ?? true);
        }

        $key = Str::snake($name, '.');

        if (isset($arguments['default'])) {
            $this->defaults[$key] = $arguments['default'];
        }

        if (isset($arguments['modifier'])) {
            $this->modifiers[$key] = $arguments['modifier'];
        }

        // modify: ... is an alias for modifier: ...
        if (isset($arguments['modify'])) {
            $this->modifiers[$key] = $arguments['modify'];
        }

        if (isset($arguments[0])) {
            $this->set($key, $arguments[0]);
        }

        if (isset($arguments[0]) || isset($arguments['default']) || isset($arguments['modifier']) || isset($arguments['modify'])) {
            return $this;
        }

        return $this->get($key);
    }

   
    public function __get(string $key): string|null
    {
        return $this->get(Str::snake($key, '.'));
    }

   
    public function __set(string $key, string $value)
    {
        return $this->set(Str::snake($key, '.'), $value);
    }
}
