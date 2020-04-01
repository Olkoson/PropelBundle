<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * PanelController is designed to display information in the Propel Panel.
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PanelController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * This method renders the global Propel configuration.
     * @param Environment $twig
     * @param \PropelConfiguration $propelConfiguration
     *
     * @return \Symfony\Component\HttpFoundation\Response A Response instance
     */
    public function configurationAction(Environment $twig, \PropelConfiguration $propelConfiguration, ParameterBagInterface $parameterBag)
    {
        return new Response(
            $twig->render(
                '@Propel/Panel/configuration.html.twig',
                array(
                    'propel_version' => \Propel::VERSION,
                    'configuration' => $propelConfiguration->getParameters(),
                    'default_connection' => $parameterBag->get('propel.dbal.default_connection'),
                    'logging' => $parameterBag->get('propel.logging'),
                    'path' => $parameterBag->get('propel.path'),
                    'phing_path' => $parameterBag->get('propel.phing_path'),
                )
            )
        );
    }

    /**
     * Renders the profiler panel for the given token.
     *
     * @param string $token The profiler token
     * @param string $connection The connection name
     * @param integer $query
     *
     * @return \Symfony\Component\HttpFoundation\Response A Response instance
     */
    public function explainAction(Environment $twig, $token, $connection, $query)
    {
        $profiler = $this->container->get('profiler');
        $profiler->disable();

        $profile = $profiler->loadProfile($token);
        $queries = $profile->getCollector('propel')->getQueries();

        if (!isset($queries[$query])) {
            return new Response('This query does not exist.');
        }

        // Open the connection
        $con = \Propel::getConnection($connection);

        // Get the adapter
        $db = \Propel::getDB($connection);

        try {
            $stmt = $db->doExplainPlan($con, $queries[$query]['sql']);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return new Response('<div class="error">This query cannot be explained.</div>');
        }

        return new Response(
            $twig->render(
                '@Propel/Panel/explain.html.twig',
                array(
                    'data' => $results,
                    'query' => $query,
                )
            )
        );
    }
}
