<?php

namespace Stof\DoctrineExtensionsBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineExtensionsExtension extends Extension
{
    public function configLoad(array $configs, ContainerBuilder $container)
    {
        $defaultListeners = array (
            'tree' => true,
            'timestampable' => true,
            'translatable' => true,
            'sluggable' => true,
        );
        $loader = new XmlFileLoader($container, __DIR__.'/../Resources/config');
        $config = $this->mergeConfigurations($configs);

        if (isset($config['orm'])) {
            $loader->load('orm.xml');

            $entity_managers = array ();
            $emConfig = $config['orm'];
            foreach ($emConfig as $name => $listeners){
                if (null === $listeners){
                    $listeners = array ();
                }
                if (isset($listeners['id'])) {
                    $name = $listeners['id'];
                    unset ($listeners['id']);
                }
                $entity_managers[$name] = array_merge($defaultListeners, $listeners);
            }
            $container->setParameter('stof_doctrine_extensions.orm.entity_managers', $entity_managers);
        }

        if (isset($config['mongodb'])) {
            $loader->load('mongodb.xml');

            $document_managers = array ();
            $mongodbConfig = $config['mongodb'];
            foreach ($mongodbConfig as $name => $listeners){
                if (null === $listeners){
                    $listeners = array ();
                }
                if (isset($listeners['id'])) {
                    $name = $listeners['id'];
                    unset ($listeners['id']);
                }
                $document_managers[$name] = array_merge($defaultListeners, $listeners);
            }
            $container->setParameter('stof_doctrine_extensions.odm.mongodb.document_managers', $document_managers);
        }

        if (isset($config['class'])) {
            $this->remapParametersNamespaces($config['class'], $container, array(
                'orm'       => 'stof_doctrine_extensions.orm.listener.%s.class',
                'mongodb'   => 'stof_doctrine_extensions.odm.mongodb.listener.%s.class',
            ));
        }
    }

    protected function remapParameters(array $config, ContainerBuilder $container, array $map)
    {
        foreach ($map as $name => $paramName) {
            if (isset($config[$name])) {
                $container->setParameter($paramName, $config[$name]);
            }
        }
    }

    protected function remapParametersNamespaces(array $config, ContainerBuilder $container, array $namespaces)
    {
        foreach ($namespaces as $ns => $map) {
            if ($ns) {
                if (!isset($config[$ns])) {
                    continue;
                }
                $namespaceConfig = $config[$ns];
            } else {
                $namespaceConfig = $config;
            }
            if (is_array($map)) {
                $this->remapParameters($namespaceConfig, $container, $map);
            } else {
                foreach ($namespaceConfig as $name => $value) {
                    if (null !== $value) {
                        $container->setParameter(sprintf($map, $name), $value);
                    }
                }
            }
        }
    }

    /**
     * Merges the configurations
     *
     * @param array $configs
     * @return array
     */
    protected function mergeConfigurations(array $configs)
    {
        $config = array ();
        foreach ($configs as $file) {
            $config = $this->mergeRecursively($config, $file);
        }

        return $config;
    }

    /**
     * Merges two configurations recursively
     *
     * @param array $oldConfig
     * @param array $newConfig
     * @return array
     */
    protected function mergeRecursively(array $oldConfig, array $newConfig)
    {
        foreach ($newConfig as $key => $value) {
            if (!is_array($value)) {
                $oldConfig[$key] = $value;
            } elseif (!array_key_exists($key, $oldConfig)) {
                $oldConfig[$key] = $value;
            } else {
                $oldConfig[$key] = $this->mergeRecursively($oldConfig[$key], $value);
            }
        }

        return $oldConfig;
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return null;
    }

    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/stof_doctrine_extensions';
    }

    public function getAlias()
    {
        return 'stof_doctrine_extensions';
    }
}
