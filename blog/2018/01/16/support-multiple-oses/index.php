<?php require("phplib/content-funcs.php"); ?>

<!DOCTYPE html>
<html lang="en">

<head>
<?php require("phplib/partials/head.html"); ?>
<title>How to Support Multiple OSes with One Mac</title>
</head>

<body>
  <!-- NAV BAR -->
  <?php makeSubpageNavbar("blog"); ?>

  <main class="container">
  <article>
    <header>
      <h1>How to Support Multiple OSes with One Mac</h1>
      <p>
        <small>C. Dylan Shearer | 16 Jan 2018</small>
      </p>
    </header>

    <p>Making operating-system specific packages of your project can be a great
      service to your users. But it's also a pain. Linux distributions differ in
      which package-management system they use, and you need to take the time to
      learn how to do it correctly. In the end, the scripts you write to make
      these packages are really just more pieces of your project, and they
      should be covered by automated tests along with the rest of it.</p>

    <p>In an enterprise setting (or an open-source project with funding), we
      would have a Jenkins server for each OS that we support, and building and
      testing packages would be part of our Continuous Integration routine.
      Unfortunately, this is not an option for most open-source projects.</p>

    <p>
      In this article, I present a scheme (really, a bunch of Make files and a
      certain directory structure) that can test these packaging scripts for an
      arbitrary number of different (Unix) OSes on your own dev box. I came up
      with it while working on making packages for jobber.
    </p>

    <p>An important benefit of this scheme is that it can easily incorporate any
      automated system tests you may have, making it very easy for you to ensure
      that your program works on all the OSes you claim to support.</p>

    <p>
      I have made a toy project that uses this system: <a
        href="https://github.com/dshearer/polly">polly</a>, named after a cat I
      had who was happy to pack up and travel with me regularly. I'll use it as
      an example, showing how this scheme can be used to add support for CentOS
      7 and Debian 9.
    </p>

    <h2 id="packagingoutofscope">Packaging: Out of Scope</h2>

    <p>
      This article will not go into the details of how to make packages for
      different OSes. However, the toy project does provide a good starting
      point if you need to make RPMs or Debian packages for non-daemon programs.
      If you'd like an example of how to do it for a daemon, take a look at <a
        href="https://github.com/dshearer/jobber/tree/master/packaging">what I
      did for Jobber</a>.
    </p>

    <h2 id="prereqs">Prereqs</h2>

    <p>Here are the tools you'll need:</p>

    <ul>
      <li><a href="https://www.gnu.org/software/make/">GNU Make</a></li>

      <li><a href="https://www.vagrantup.com/">Vagrant</a></li>

      <li><a href="https://www.virtualbox.org/wiki/Downloads">VirtualBox</a></li>
    </ul>

    <p>VirtualBox is open-source virtualization software.</p>

    <p>Vagrant is the key to this scheme. It is a tool that makes it easy to
      automate creation, booting, shutdown, etc. of VMs. It's like Docker for
      VMs.</p>

    <p>The toy project is written in Go, but you don't need the Go compiler on
      your system, as we'll do all the compilation on VMs.</p>

    <h1 id="whatitdoes">What It Does</h1>

    <p>
      To get started, please clone polly and then check out tag
      <code>initial</code>.
    </p>

    <pre><code>$ git clone https://github.com/dshearer/polly.git
$ cd polly
$ git checkout initial</code></pre>

    <p>This wonderful Go project initially looks like this:</p>

    <pre><code>|- src/github.com/dshearer/polly
    |- main.go
    |- meow.go
    |- meow_test.go</code></pre>

    <p>If you have Go installed, you can play around with it:</p>

    <pre><code>$ go test
PASS
ok      .../polly   0.006s
$ go build
$ ./polly
meow! meow! meow! meow! meow!</code></pre>

    <p>
      Let's now take a look at how our scheme adds support for CentOS 7 and
      Debian 9. Please check out the tip of master (<code>git checkout master</code>).
    </p>

    <p>The project now looks like this:</p>

    <pre><code>|- src/github.com/dshearer/polly
    |- Makefile
    |- main.go
    |- meow.go
    |- meow_test.go
    |- packaging/
        |- Makefile
        |- centos_7/
            |- Makefile
            |- Vagrantfile
            |- polly.spec
            |- sources.mk
        |- debian_9/
            |- Makefile
            |- Vagrantfile
            |- debian-pkg/
                ...
            |- sources.mk
        |- head.mk
        |- sources.mk
        |- tail.mk
    |- system_test/
        |- meow_test.sh
    |- sources.mk</code></pre>

    <p>
      Yes, there's a lot of new crap, but making Linux packages isn't exactly
      simple. Most of the new files are in the directory
      <code>packaging</code>, in which we have one subdirectory for each of the OSes we wish to
      support &mdash;
      <code>centos_7</code> and <code>debian_9</code>.
      <code>packaging/centos_7/polly.spec</code>
      is our RPM spec file that we'll use to make the CentOS 7 package, and
      <code>packaging/debian_9/debian-pkg</code>
      contains all the standard files needed for making a Debian package.
    </p>

    <p>
      We also have a new file at
      <code>system_test/meow_test.sh</code>. This script contains any 
      system tests that should be done on the program
      after it is installed.
    </p>

    <p>So what does this give us? If you have installed Vagrant and VirtualBox,
      try this:</p>

    <pre><code>$ make -C packaging -j test-vm</code></pre>

    <p>(The &ldquo;-j&rdquo; option causes this to be done for each OS in parallel.
      Occasionally, I have seen this brick the VMs, which will cause the command
      to hang for a while. If this happens to you, try the command without
      &ldquo;-j&rdquo;.)</p>

    <p>
      When this command is done, you will find a shiny new RPM at
      <code>packaging/results/centos_9/polly-1.0-1.el7.centos.x86_64.rpm</code>
      and a shiny new Debian package at
      <code>packaging/results/debian_9/polly_1.0-1_amd64.deb</code>.
      Also, those packages will have been tested to ensure that they install
      polly correctly, and polly will have been tested to ensure it works on
      each of those OSes, using
      <code>system_test/meow_test.sh</code>.
       You can see the results of the tests thus:
    </p>

    <pre><code>$ tail results/centos_7/test-vm.log
Installed:
  polly.x86_64 0:1.0-1.el7.centos                                               

Complete!
# run test
vagrant ssh --no-tty -c 'make -C polly-1.0/packaging/centos_7 test-local'
make: Entering directory `/home/vagrant/polly-1.0/packaging/centos_7'
"/home/vagrant/polly-1.0/system_test/meow_test.sh"
PASS
make: Leaving directory `/home/vagrant/polly-1.0/packaging/centos_7'

$ tail results/debian_9/test-vm.log 
(Reading database ... 41645 files and directories currently installed.)
Preparing to unpack polly_1.0-1_amd64.deb ...
Unpacking polly (1.0-1) ...
Setting up polly (1.0-1) ...
# run test
vagrant ssh --no-tty -c 'make -C polly-1.0/packaging/debian_9 test-local'
make: Entering directory '/home/vagrant/polly-1.0/packaging/debian_9'
"/home/vagrant/polly-1.0/system_test/meow_test.sh"
PASS
make: Leaving directory '/home/vagrant/polly-1.0/packaging/debian_9'</code></pre>

    <h1 id="howitdoesitoverview">How It Does It (Overview)</h1>

    <p>This whole process is orchestrated by Make files. I know Make isn't used
      as much anymore, but it really does work well. Moreover, it makes it much
      easier to build packages if your project can be built and installed with
      Make.</p>

    <p>Making a package on both CentOS and Debian involves several OS-specific
      steps and OS-specific tools (namely, rpmbuild for CentOS and
      dpkg-buildpackage for Debian). We of course need to automate those steps.
      We also need to automate the steps that will be executed on our host
      machine &mdash; for example, creating and starting the VMs. We might expect to
      be able to break our automation code into the following mutually exclusive
      categories:</p>

    <ul>
      <li>Automation code that is to be run on the host</li>

      <li>Automation code that is to be run on a CentOS 7 VM</li>

      <li>Automation code that is to be run on a Debian 9 VM</li>
    </ul>

    <p>But it turns out that there's some overlap &mdash; specifically, we need to
      run some of the code from the first category on the VMs. So our solution
      takes this approach:</p>

    <ol>
      <li>Make one system of Make files that does everything we need for every
        platform &mdash; builds the program, builds the packages, runs unit tests,
        etc. &mdash; ignoring the fact that not all these commands can actually be
        run on the same OS</li>

      <li>Add logic that &ldquo;magically&rdquo; switches from the host to, say, the Debian
        VM, and then resumes execution on the VM</li>
    </ol>

    <h1 id="howitdoesitdetails">How It Does It (Details)</h1>

    <p>
      At the root of the project is the main Make file &mdash;
      <code>Makefile</code>.
      Its important targets are
    </p>

    <ul>
      <li><code>build</code>: build the program (actually, it calls <code>go
          install</code>)</li>

      <li><code>install</code>: install the program to the appropriate place
        (for example, <code>/usr/local/bin</code>)</li>

      <li><code>check</code>: run unit tests</li>

      <li><code>dist</code>: make a source tarball</li>
    </ul>

    <p>
      (The <code>dist</code> target is the reason for all those <code>sources.mk</code>
      files: those files list the source files in their respective directories,
      and <code>Makefile</code>
      imports them all to make the final list of all source files to be included
      in the tarball.)
    </p>

    <p>
      Importantly, the main Make file does not concern itself with making
      packages or any other OS-specific activities. That stuff is covered by
      <code>packaging/centos_7/Makefile</code>
      and
      <code>packaging/debian_9/Makefile</code>.
      Both of these contain the following targets:
    </p>

    <ul>
      <li><code>pkg-local</code>: build the OS-specific package (assuming we are
        on a VM)</li>

      <li><code>test-local</code>: Run <code>system_test/meow_test.sh</code>
        (assuming we are on a VM)</li>

      <li><code>pkg-vm</code>: &ldquo;Magically&rdquo; run <code>pkg-local</code> on a VM
        with the appropriate OS (assuming we are on the host)</li>

      <li><code>test-vm</code>: Run <code>pkg-vm</code>, then install the
        package on a VM with the appropriate OS, and finally &ldquo;agically&rdquo; 
        run <code>test-local</code>
        on the VM (assuming we are on the host)</li>
    </ul>

    <p>
      I want to be clear about something.
      <code>packaging/centos_7/Makefile</code>
      and
      <code>packaging/debian_9/Makefile</code>
      <em>contain</em>
      <code>test-local</code>,
      <code>pkg-vm</code>, and
      <code>test-vm</code>, but these targets are <em>defined</em> in
      <code>packaging/tail.mk</code>, which those two Make files import. In general,
      <code>packaging/&lt;some_os&gt;/Makefile</code>
      should define only OS-specific stuff.
    </p>

    <p>
      For your convenience, here is the only target in
      <code>packaging/debian_9/Makefile</code>:
    </p>

    <pre><code>.PHONY : pkg-local
pkg-local : ${WORK_DIR}/${SRC_TARBALL}
    cp "${WORK_DIR}/${SRC_TARBALL}" \
        "${SRC_ROOT}/../polly_${VERSION}.orig.tar.gz"
    cp -R debian-pkg "${SRC_ROOT}/debian"
    cd "${SRC_ROOT}" &amp;&amp; dpkg-buildpackage -us -uc
    mkdir -p "${DESTDIR}/"
    mv "${SRC_ROOT}"/../*.deb "${DESTDIR}/"</code></pre>

    <p>
      Lastly,
      <code>packaging/Makefile</code>
      also has targets
      <code>pkg-vm</code>
      and
      <code>test-vm</code>,
      but they just recursively call the same targets in each of the
      OS-specific subdirectories' Make files.
    </p>

    <h2 id="themagicparts">The &ldquo;Magical&rdquo; Parts</h2>

    <p>
      The implementation of the &ldquo;magical&rdquo; parts is actually quite straightforward.
      When
      <code>make -C packaging/&lt;some_os&gt; pkg-vm</code>
      is called, the Make file
    </p>

    <ol>
      <li>Uses the main Make file's <code>dist</code> target to make a source
        tarball
      </li>

      <li>Makes (or starts) a VM with the needed OS</li>

      <li>Copies the tarball to the VM</li>

      <li>Expands the tarball on the VM</li>

      <li>Runs <code>make -C packaging/&lt;some_os&gt; pkg-local</code> on the
        VM
      </li>
    </ol>

    <p>
      <code>test-vm</code>
      calls
      <code>test-local</code>
      on the VM in a similar way.
    </p>

    <p>
      Vagrant comes into play in steps 2-5.
      <code>packaging/centos_7/Vagrantfile</code>
      and
      <code>packaging/debian_9/Vagrantfile</code>
      specify the VM image we wish to start with and the steps needed to set it
      up. For your convenience, here's the CentOS one:
    </p>

    <pre><code>Vagrant.configure("2") do |config|
    config.vm.box = "centos/7"

    config.vm.network :forwarded_port, guest: 22, host: 2223, id: "ssh"

    config.vm.provision "shell", inline: &lt;&lt;-SHELL
        yum install -y epel-release rpm-build
        yum install -y golang
    SHELL
end</code></pre>

    <p>
      When the Make file uses the
      <code>vagrant</code>
      command to create, start, etc. the VM, Vagrant looks to
      <code>Vagrantfile</code>
      for the details.
    </p>

    <p>When you do all this for the first time, it takes a while for Vagrant to
      download the VM images and create the VMs. Happily, Vagrant will cache the
      images so that subsequent runs will not download them again.</p>

    <p>
      In addition, the Make file takes a snapshot of each VM just after it is
      made. If you run
      <code>make -C packaging/&lt;some_os&gt; pkg-vm</code>
      or
      <code>make -C packaging/&lt;some_os&gt; test-vm</code>
      multiple times, they will reuse the VM, and any files from previous runs
      will still be on the VM. To revert to the pristine snapshot, do
      <code>make -C packaging/&lt;some_os&gt; clean</code>
      before calling the other targets.
    </p>

    <h1 id="thenextstep">The Next Step</h1>

    <p>
      I mentioned in the intro that this scheme naturally supports running
      system tests on each of these OSes. In polly,
      <code>system_test/meow_test.sh</code>
      is a tiny toy system test for a tiny toy program. In a real project, this
      opportunity to make system testing a part of your development process
      should not be ignored.
    </p>

    <p>
      Jobber has <a
        href="https://github.com/dshearer/jobber/tree/master/platform_tests">a
        good example</a>, containing tests using <a
        href="http://robotframework.org/">Robot Framework</a> of every major
      feature. When I'm working on Jobber, I just need to do
      <code>make -C packaging -j test-vm</code>
      and watch the tests run on every supported OS, in parallel. When they are
      done, there will be a beautiful Robot test report for each OS waiting for
      me in <code>packaging/results</code>.
    </p>

    <h1 id="conclusion">Conclusion</h1>

    <p>Supporting multiple operating systems is hard, especially if you don't
      have infrastructure that automates away a lot of the tedious parts. The
      scheme presented in this article provides such infrastructure, and I hope
      it has some ideas that would be useful in your own projects.</p>
  </article>
  </main>

  <!-- FOOTER  -->
  <footer class="small">
    <p>Copyright &#0169; 2018 C.&nbsp;Dylan Shearer</p>
  </footer>
</body>

</html>