import { Link } from "react-router-dom"
import BannerGlobal from "../components/BannerGlobal/BannerGlobal"
import Botao from "../components/Botao/Botao"
import NavBar from "../components/NavBar/NavBar"
import ButtonCard from "../components/ButtonCard/ButtonCard"
import { useEffect } from "react"
import DadosJson from "../assets/json/dados_db.json"

function Home() {

    useEffect(()=>{
        document.title = "SGM - Home"
    },[])
    return (
        < >
            <BannerGlobal />
            <Link to={"/NovaEdicao"}>
                <Botao> <img src="/icons/plus-icon.svg" alt="Mais" /> Criar Nova edição</Botao>
            </Link>
            <section className="w-full py-10 flex flex-col justify-center ">
                {DadosJson.edicoes.map(edicao => 
                    <Link to={"/Interclasse"}>
                        <ButtonCard
                            key={edicao.id}
                            titulo={edicao.titulo}
                            status={edicao.status}
                        />
                    </Link>
                )}
            </section>
            <NavBar />
        </>
    )
}

export default Home