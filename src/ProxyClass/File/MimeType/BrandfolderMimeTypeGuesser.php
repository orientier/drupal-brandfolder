<?php
// phpcs:ignoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\brandfolder\File\MimeType\BrandfolderMimeTypeGuesser' "web/modules/composer/brandfolder/src".
 */

namespace Drupal\brandfolder\ProxyClass\File\MimeType {

    use Symfony\Component\Mime\MimeTypeGuesserInterface;
    use Symfony\Component\Mime\MimeTypesInterface;
    use Drupal\Core\DependencyInjection\DependencySerializationTrait;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    /**
     * Provides a proxy class for \Drupal\brandfolder\File\MimeType\BrandfolderMimeTypeGuesser.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class BrandfolderMimeTypeGuesser implements MimeTypeGuesserInterface
    {

        use DependencySerializationTrait;

        /**
         * The id of the original proxied service.
         *
         * @var string
         */
        protected $drupalProxyOriginalServiceId;

        /**
         * The real proxied service, after it was lazy loaded.
         *
         * @var \Drupal\brandfolder\ProxyClass\File\MimeType\BrandfolderMimeTypeGuesser
         */
        protected $service;

        /**
         * The service container.
         *
         * @var \Symfony\Component\DependencyInjection\ContainerInterface
         */
        protected $container;

        /**
         * Constructs a ProxyClass Drupal proxy object.
         *
         * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
         *   The container.
         * @param string $drupal_proxy_original_service_id
         *   The service ID of the original service.
         */
        public function __construct(ContainerInterface $container, $drupal_proxy_original_service_id)
        {
            $this->container = $container;
            $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
        }

        /**
         * Lazy loads the real service from the container.
         *
         * @return object
         *   Returns the constructed real service.
         */
        protected function lazyLoadItself()
        {
            if (!isset($this->service)) {
                $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
            }

            return $this->service;
        }

        /**
         * {@inheritdoc}
         */
        public function guessMimeType($path): ?string
        {
            return $this->lazyLoadItself()->guessMimeType($path);
        }

        /**
         * {@inheritdoc}
         */
        public function guess($path)
        {
            return $this->lazyLoadItself()->guess($path);
        }

        /**
         * {@inheritdoc}
         */
        public function setMapping(?array $mapping = NULL)
        {
            return $this->lazyLoadItself()->setMapping($mapping);
        }

        /**
         * {@inheritdoc}
         */
        public function isGuesserSupported(): bool
        {
            return $this->lazyLoadItself()->isGuesserSupported();
        }

    }

}
