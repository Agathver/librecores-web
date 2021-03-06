<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use App\Util\Controllers;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    /**
     * Render the project overview page
     *
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig');
    }

    /**
     * Display a user or an organization
     *
     * @param string                 $userOrOrganization
     * @param OrganizationRepository $organizationRepository
     * @param UserRepository         $userRepository
     *
     * @return Response
     *
     * @throws NonUniqueResultException
     */
    public function userOrgViewAction(
        string $userOrOrganization,
        OrganizationRepository $organizationRepository,
        UserRepository $userRepository
    ) {
        $uoo = $this->getUserOrOrg(
            $userOrOrganization,
            $organizationRepository,
            $userRepository
        );

        if ($uoo instanceof User) {
            return $this->forward(
                Controllers::get(UserController::class, 'viewAction'),
                array('user' => $uoo)
            );
        }
        if ($uoo instanceof Organization) {
            return $this->forward(
                Controllers::get(OrganizationController::class, 'viewAction'),
                array('organization' => $uoo)
            );
        }

        // and 404 if it's neither
        throw $this->createNotFoundException('User or organization not found.');
    }

    /**
     * Display the organization settings page
     *
     * @param string                 $userOrOrganization     name of the user
     *                                                       or organization
     * @param OrganizationRepository $organizationRepository autowired
     * @param UserRepository         $userRepository         autowired
     *
     * @return Response
     *
     * @throws NonUniqueResultException
     */
    public function userOrgSettingsAction(
        $userOrOrganization,
        OrganizationRepository $organizationRepository,
        UserRepository $userRepository
    ) {
        $uoo = $this->getUserOrOrg(
            $userOrOrganization,
            $organizationRepository,
            $userRepository
        );

        if ($uoo instanceof User) {
            if ($uoo->getId() !== $this->getUser()->getId()) {
                throw $this->createAccessDeniedException();
            }

            return $this->forward(
                Controllers::get(UserController::class, 'profileSettingsAction'),
                array('user' => $userOrOrganization)
            );
        }

        if ($uoo instanceof Organization) {
            return $this->forward(
                Controllers::get(OrganizationController::class, 'settingsAction'),
                array('organization' => $uoo)
            );
        }

        // and 404 if it's neither
        throw $this->createNotFoundException('User or organization not found.');
    }

    /**
     * Search for a project
     *
     * @param Request $req
     *
     * @return Response
     */
    public function searchAction(Request $req)
    {
        $searchType = $req->get('type');
        $searchQuery = $req->get('query');

        $searchType = ($searchType === null ? 'projects' : $searchType);

        return $this->render(
            'default/project_search.html.twig',
            [
                'searchType' => $searchType,
                'searchQuery' => $searchQuery,
            ]
        );
    }


    public function removeTrailingSlashAction(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        $requestUri = $request->getRequestUri();

        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        return $this->redirect($url, 301);
    }

    /**
     * Get an user or organization entity with the given name
     *
     * @param string                 $userOrOrganization
     * @param OrganizationRepository $organizationRepository
     * @param UserRepository         $userRepository
     *
     * @return User|Organization|null user or organization entity, or null if
     *   no entity with the given name exists.
     * @throws NonUniqueResultException
     */
    private function getUserOrOrg(
        $userOrOrganization,
        OrganizationRepository $organizationRepository,
        UserRepository $userRepository
    ) {
        // try user first
        $user = $userRepository->findOneByUsername($userOrOrganization);

        if ($user !== null) {
            return $user;
        }

        // then organization
        $org = $organizationRepository->findOneByName($userOrOrganization);

        return $org;
    }
}
