<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Bundle\CouchDBBundle\Tests;

use Doctrine\Bundle\CouchDBBundle\DoctrineCouchDBBundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

class BundleTest extends TestCase
{
    public function testRegisterCompilerPasses()
    {
        $bundle = new DoctrineCouchDBBundle();
        $builder = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        
        $builder->expects($this->at(0))
                ->method('addCompilerPass')
                ->with(
                    $this->isInstanceOf('Doctrine\Bundle\CouchDBBundle\DependencyInjection\Compiler\RegisterEventListenersAndSubscribersPass'),
                    $this->equalTo(PassConfig::TYPE_BEFORE_OPTIMIZATION)
                );
        $builder->expects($this->at(1))
                ->method('addCompilerPass')
                ->with(
                    $this->isInstanceOf('Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\DoctrineValidationPass'),
                    $this->equalTo(PassConfig::TYPE_BEFORE_OPTIMIZATION)
                );

        $bundle->build($builder);
    }
}
