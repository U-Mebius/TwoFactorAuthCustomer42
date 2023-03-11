<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\TwoFactorAuthCustomer42\Repository;

use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use Eccube\Entity\Customer;
use Eccube\Repository\AbstractRepository;
use Eccube\Util\StringUtil;
use Plugin\TwoFactorAuthCustomer42\Entity\TwoFactorAuthCustomerCookie;

/**
 * TwoFactorAuthConfigRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TwoFactorAuthCustomerCookieRepository extends AbstractRepository
{
    /**
     * TwoFactorAuthConfigRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TwoFactorAuthCustomerCookie::class);
    }

    /**
     * ２段階認証クッキーの発行
     *
     * @param Customer $customer
     * @param string $cookieName
     * @param int $expireSeconds
     * @param int $CookieValueCharacterLength
     *
     * @return TwoFactorAuthCustomerCookie
     */
    public function generateCookieData(
        Customer $customer,
        string $cookieName,
        int $expireSeconds,
        int $CookieValueCharacterLength
    ): TwoFactorAuthCustomerCookie {
        /** @var TwoFactorAuthCustomerCookie[]|null $previousCookies */
        $previousCookies = $this->findOldCookies($customer, $cookieName);
        foreach ($previousCookies as $cookie) {
            $this->getEntityManager()->remove($cookie);
        }
        $this->getEntityManager()->flush();

        $cookie = new TwoFactorAuthCustomerCookie();
        $cookie->setCookieName($cookieName);
        $cookie->setCookieValue(StringUtil::random($CookieValueCharacterLength));
        $cookie->setCookieExpireDate($expireSeconds != 0 ? Carbon::now()->addSeconds($expireSeconds) : null);
        $cookie->setCustomer($customer);
        $cookie->updatedTimestamps();

        return $cookie;
    }

    /**
     * @return $result
     */
    public function findOne()
    {
        return $this->findOneBy([], ['id' => 'DESC']);
    }

    /***
     * 有効クッキーを取得する
     *
     * @param Customer $customer
     * @param string $cookieName
     * @return TwoFactorAuthCustomerCookie[]|null
     */
    public function searchForCookie(Customer $customer, string $cookieName)
    {
        $expireDate = Carbon::now()->setTimezone('UTC')->format('Y-m-d H:i:s');

        $something = $this->createQueryBuilder('tfcc')
            ->where('tfcc.Customer = :customer_id')
            ->andWhere('tfcc.cookie_name = :cookie_name')
            ->andWhere('tfcc.cookie_expire_date > :expire_date')
            ->setParameters([
                'customer_id' => $customer->getId(),
                'cookie_name' => $cookieName,
                'expire_date' => $expireDate,
            ])
            ->getQuery()->getSQL();

        return $this->createQueryBuilder('tfcc')
            ->where('tfcc.Customer = :customer_id')
            ->andWhere('tfcc.cookie_name = :cookie_name')
            ->andWhere('tfcc.cookie_expire_date > :expire_date')
            ->setParameters([
                'customer_id' => $customer->getId(),
                'cookie_name' => $cookieName,
                'expire_date' => $expireDate,
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * 過去のクッキーデータの取得
     *
     * @param Customer $customer
     * @param string $cookieName
     * @return float|int|mixed|string
     */
    public function findOldCookies(Customer $customer, string $cookieName)
    {
        $expireDate = Carbon::now()->setTimezone('UTC')->format('Y-m-d H:i:s');

        return $this->createQueryBuilder('tfcc')
            ->where('tfcc.Customer = :customer_id')
            ->andWhere('tfcc.cookie_name = :cookie_name')
            ->andWhere('tfcc.cookie_expire_date < :expire_date')
            ->setParameters([
                'customer_id' => $customer->getId(),
                'cookie_name' => $cookieName,
                'expire_date' => $expireDate,
            ])
            ->getQuery()
            ->getResult();
    }
}
