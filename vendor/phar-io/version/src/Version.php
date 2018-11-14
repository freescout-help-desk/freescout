<?php
/*
 * This file is part of PharIo\Version.
 *
 * (c) Arne Blankerts <arne@blankerts.de>, Sebastian Heuer <sebastian@phpeople.de>, Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PharIo\Version;

class Version {
    /**
     * @var VersionNumber
     */
    private $major;

    /**
     * @var VersionNumber
     */
    private $minor;

    /**
     * @var VersionNumber
     */
    private $patch;

    /**
     * @var PreReleaseSuffix
     */
    private $preReleaseSuffix;

    /**
     * @var string
     */
    private $versionString = '';

    /**
     * @param string $versionString
     */
    public function __construct($versionString) {
        $this->ensureVersionStringIsValid($versionString);

        $this->versionString = $versionString;
    }

    /**
     * @param array $matches
     */
    private function parseVersion(array $matches) {
        $this->major = new VersionNumber($matches['Major']);
        $this->minor = new VersionNumber($matches['Minor']);
        $this->patch = isset($matches['Patch']) ? new VersionNumber($matches['Patch']) : new VersionNumber(null);

        if (isset($matches['ReleaseType'])) {
            $preReleaseNumber = isset($matches['ReleaseTypeCount']) ? (int) $matches['ReleaseTypeCount'] : null;

            $this->preReleaseSuffix = new PreReleaseSuffix($matches['ReleaseType'], $preReleaseNumber);
        }
    }

    /**
     * @return PreReleaseSuffix
     */
    public function getPreReleaseSuffix()
    {
        return $this->preReleaseSuffix;
    }

    /**
     * @return string
     */
    public function getVersionString() {
        return $this->versionString;
    }

    /**
     * @param Version $version
     *
     * @return bool
     */
    public function isGreaterThan(Version $version) {
        if ($version->getMajor()->getValue() > $this->getMajor()->getValue()) {
            return false;
        }

        if ($version->getMajor()->getValue() < $this->getMajor()->getValue()) {
            return true;
        }

        if ($version->getMinor()->getValue() > $this->getMinor()->getValue()) {
            return false;
        }

        if ($version->getMinor()->getValue() < $this->getMinor()->getValue()) {
            return true;
        }

        if ($version->getPatch()->getValue() >= $this->getPatch()->getValue()) {
            return false;
        }

        if ($version->getPatch()->getValue() < $this->getPatch()->getValue()) {
            return true;
        }

        return false;
    }

    /**
     * @return VersionNumber
     */
    public function getMajor() {
        return $this->major;
    }

    /**
     * @return VersionNumber
     */
    public function getMinor() {
        return $this->minor;
    }

    /**
     * @return VersionNumber
     */
    public function getPatch() {
        return $this->patch;
    }

    /**
     * @param string $version
     *
     * @throws InvalidVersionException
     */
    private function ensureVersionStringIsValid($version) {
        $regex = '/^v?
            (?<Major>(0|(?:[1-9][0-9]*)))
            \\.
            (?<Minor>(0|(?:[1-9][0-9]*)))
            (\\.
                (?<Patch>(0|(?:[1-9][0-9]*)))
            )?
            (?:
                -
                (?<ReleaseType>(?:(dev|beta|b|RC|alpha|a|patch|p)))
                (?:
                    (?<ReleaseTypeCount>[0-9])
                )?
            )?       
        $/x';

        if (preg_match($regex, $version, $matches) !== 1) {
            throw new InvalidVersionException(
                sprintf("Version string '%s' does not follow SemVer semantics", $version)
            );
        }

        $this->parseVersion($matches);
    }
}
